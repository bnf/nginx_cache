#!/bin/bash
set -ev

DIR=$(realpath $(dirname "$0"))
ROOT=$(realpath "$DIR/..")

#ROOT=/home/ben/htdocs/t3master7

function clear_cache() {
	cd $ROOT
	$ROOT/.Build/bin/typo3cms cache:flush
	#$ROOT/typo3cms cache:flush
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
	echo $content | grep -q "X-Cache: $nginx"

	echo "Testing typo3 state $content"
	echo $content | grep -q "X-TYPO3-Cache: $typo3"
}

clear_cache
test_hit / MISS MISS
test_hit / HIT MISS

clear_cache
test_hit / MISS MISS -I
test_hit / MISS HIT
test_hit / HIT HIT

clear_cache
test_hit / MISS MISS
test_hit /index.php MISS HIT
test_hit /index.php HIT HIT

clear_cache
test_hit / MISS MISS -I
test_hit /index.php MISS HIT
test_hit / MISS HIT -I
test_hit / MISS HIT
test_hit / HIT HIT  -I

clear_cache
# TODO backend login, writing a cookie $COOKIEFILE
# TODO: place adminpanel on root page
#test_hit / MISS MISS  -b $COOKIEFILE
#test_hit / MISS HIT -b $COOKIEFILE
#test_hit / MISS HIT
#test_hit / HIT HIT
