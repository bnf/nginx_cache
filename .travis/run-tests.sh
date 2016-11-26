#!/bin/bash
#set -ev

DIR=$(realpath $(dirname "$0"))
ROOT=$(realpath "$DIR/..")

source $DIR/assert.sh

cookiefile=/tmp/nginx_cache-backend.cookie

function clear_cache() {
	cd $ROOT
	$ROOT/.Build/bin/typo3cms cache:flush
	cd -
	curl -X PURGE $HOST/*
}

function test_hit() {
	url=$1
	nginx=$2
	typo3=$3
	args=$4

	content=$(curl $args -s -o /dev/null -v "${HOST}${url}" 2>&1 | grep -- "< X-\(Cache\|TYPO3-Cache\):" | sed "s/\r//g")

	echo "Testing nginx state for $content"
	echo $content | grep -q "X-Cache: $nginx" || return 1

	echo "Testing typo3 state $content"
	echo $content | grep -q "X-TYPO3-Cache: $typo3" || return 2
}

function login() {
	rm -f $cookiefile
	data=$(curl -s -L -c $cookiefile "${HOST}/typo3/")
	login_provider=$(echo $data | sed -n "s|.*\\(?loginProvider=[0-9]*\).*|\1|p")

	code=$(curl -s -w "%{http_code}" -X POST -b $cookiefile -c $cookiefile -e ${HOST}/typo3/ --data "login_status=login&userident=password&username=admin&interface=backend" "${HOST}/typo3/${login_provider}")

	if [ "$code" != 303 ]; then
		(>&2 echo "Login failed.")
		exit 1
	fi
}

clear_cache
assert_raises "test_hit / MISS MISS"
assert_raises "test_hit / HIT MISS"

clear_cache
assert_raises "test_hit / MISS MISS -I"
assert_raises "test_hit / MISS HIT"
assert_raises "test_hit / HIT HIT"

clear_cache
assert_raises "test_hit / MISS MISS"
assert_raises "test_hit /index.php MISS HIT"
assert_raises "test_hit /index.php HIT HIT"

clear_cache
assert_raises "test_hit / MISS MISS -I"
assert_raises "test_hit /index.php MISS HIT"
assert_raises "test_hit / MISS HIT -I"
assert_raises "test_hit / MISS HIT"
assert_raises "test_hit / HIT HIT  -I"

echo "UPDATE sys_template set config = REPLACE(config, 'config.admPanel = 1\n', '')" | $ROOT/.Build/bin/typo3cms database:import
clear_cache
login
assert_raises "test_hit / BYPASS MISS  '-b $cookiefile -c $cookiefile'"
assert_raises "test_hit / HIT MISS"
assert_raises "test_hit / BYPASS HIT '-b $cookiefile -c $cookiefile'"
#test_hit / MISS HIT
assert_raises "test_hit / HIT HIT"

echo "UPDATE sys_template set config = REPLACE(config, 'page = PAGE', 'config.admPanel = 1\npage = PAGE')" | $ROOT/.Build/bin/typo3cms database:import
clear_cache
login
assert_raises "test_hit / BYPASS MISS  '-b $cookiefile -c $cookiefile'"
# Cache may not be filled if admin panel is enabled
assert_raises "test_hit / MISS HIT"
assert_raises "test_hit / BYPASS HIT '-b $cookiefile -c $cookiefile'"
assert_raises "test_hit / HIT HIT"

#echo "UPDATE sys_template set config = REPLACE(config, 'page = PAGE', 'config.admPanel = 1\npage = PAGE')" | $ROOT/.Build/bin/typo3cms database:import
clear_cache
login
assert_raises "test_hit / BYPASS MISS  '-b $cookiefile -c $cookiefile'"
assert_raises "test_hit / BYPASS HIT '-b $cookiefile -c $cookiefile'"
assert_raises "test_hit / MISS HIT"
assert_raises "test_hit / HIT HIT"

assert_end "cache hit"
