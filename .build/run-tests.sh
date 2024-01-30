#!/bin/bash
#set -ev

DIR=$(realpath $(dirname "$0"))
ROOT=$(realpath "$DIR/..")
HOST="${DDEV_PRIMARY_URL}"

make -C ${ROOT} .build/assert-1.1.sh

source $DIR/assert.sh

cookiefile=/tmp/nginx_cache-backend.cookie

function clear_cache() {
	$ROOT/.build/vendor/bin/typo3 cache:flush
	curl -X PURGE "${HOST}/*"
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
	login_provider=$(echo $data | sed -n "s|.*\(?loginProvider=[0-9]*\).*|\1|p")
	request_token=$(echo $data | sed -n 's/.*<input type="hidden" name="__RequestToken" value="\([^"]*\)".*/\1/p')

	code=$(curl -s -w '%{http_code}' -X POST -b $cookiefile -c $cookiefile -e ${HOST}/typo3/ --data "login_status=login&userident=Pa%24%24w0rd&username=admin&__RequestToken=${request_token}" "${HOST}/typo3/${login_provider}" )

	if [ "$code" != 303 ]; then
		echo $code
		(>&2 echo "Login failed.")
		exit 1
	fi
}

rm -f $cookiefile

clear_cache
# (unauthorized) request misses both nginx and typo3 on first request
assert_raises "test_hit / MISS MISS"
# ..second request is the first one cached, therefore typo3 stays at MISS.
assert_raises "test_hit / HIT MISS"

clear_cache
# (unauthorized) request, fetching only header data which must not have been cached by nginx_cache
assert_raises "test_hit / MISS MISS -I"
# …as we do not want our cache to be flooded by HEAD requests, but TYPO3 actually did render the entire
# page in the first call and can therefore deliver a cached response
assert_raises "test_hit / MISS HIT"
# …which is then cached by nginx_cache and thus not returns HIT
assert_raises "test_hit / HIT HIT"

clear_cache
# (unauthorized) request
assert_raises "test_hit / MISS MISS"
# …requesting /index.php can not automatically deliver a cached response
assert_raises "test_hit /index.php MISS HIT"
# …but will then have been cached by nginx_cache for the /index.php route
# note: ideally index.php is set to be private in nginx.conf, so this would
# better return a 403…
assert_raises "test_hit /index.php HIT HIT"

clear_cache
# HEAD requests are not cached by themselves
assert_raises "test_hit / MISS MISS -I"
assert_raises "test_hit /index.php MISS HIT"
assert_raises "test_hit / MISS HIT -I"
assert_raises "test_hit / MISS HIT"
# …but will be answered cached,
# when there a corresponding GET requests has been cached before
assert_raises "test_hit / HIT HIT  -I"

echo "UPDATE sys_template set config = REPLACE(config, 'config.admPanel = 1\n', '')" | mysql db
clear_cache
login
# authorized request without adminpanel enabled
assert_raises "test_hit / BYPASS MISS  '-b $cookiefile -c $cookiefile'"
# …is cached and will therefore deliver a cached nginx response for following unauthorized requests
assert_raises "test_hit / HIT MISS"
# following authorized requests are bypassed but are served from the TYPO3 cache,
assert_raises "test_hit / BYPASS HIT '-b $cookiefile -c $cookiefile'"
#test_hit / MISS HIT
# …which will cause the nginx cache entry to be updated
# todo: ideally the update would only happen if the user pressed ctrl+shift+r(?)
assert_raises "test_hit / HIT HIT"

echo "UPDATE sys_template set config = REPLACE(config, 'page = PAGE', 'config.admPanel = 1\npage = PAGE')" | mysql db
clear_cache
login
assert_raises "test_hit / BYPASS MISS  '-b $cookiefile -c $cookiefile'"
# Cache may not have been filled if admin panel is enabled, but subsequent authorized requests…
assert_raises "test_hit / MISS HIT"
assert_raises "test_hit / BYPASS HIT '-b $cookiefile -c $cookiefile'"
# …will be served cached
assert_raises "test_hit / HIT HIT"

#echo "UPDATE sys_template set config = REPLACE(config, 'page = PAGE', 'config.admPanel = 1\npage = PAGE')" | mysql db
clear_cache
login
assert_raises "test_hit / BYPASS MISS  '-b $cookiefile -c $cookiefile'"
assert_raises "test_hit / BYPASS HIT '-b $cookiefile -c $cookiefile'"
assert_raises "test_hit / MISS HIT"
assert_raises "test_hit / HIT HIT"

assert_end "cache hit"
