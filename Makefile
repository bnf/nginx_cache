check:
	.travis/install-nginx.sh
	.travis/setup-typo3.sh ^7.6
	HOST=http://localhost:8088 .travis/run-tests.sh
	.travis/setup-typo3.sh ~8.4.0
	HOST=http://localhost:8088 .travis/run-tests.sh
	pkill -F /tmp/php-fpm.pid
	pkill -F /tmp/nginx.pid
	rm -rf .travis/nginx/
