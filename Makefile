EXTKEY = $(notdir $(shell pwd))

# Imply php 7.2 is installed from http://rpms.remirepo.net/ (for Fedora/CentOS)
# needs to be adapted if you're on Debian/Ubuntu
PHP72_PATH = "/opt/remi/php72/root/usr/sbin/:/opt/remi/php72/root/usr/bin/:${PATH}"

.travis/assert-1.1.sh:
	wget https://raw.github.com/lehmannro/assert.sh/v1.1/assert.sh -O $@
	ln -snf assert-1.1.sh .travis/assert.sh


check: .travis/assert-1.1.sh
	.travis/install-nginx.sh
	.travis/setup-typo3.sh typo3/cms:^7.6
	HOST=http://localhost:8088 .travis/run-tests.sh

	.travis/setup-typo3.sh typo3/cms:~8.7.0
	HOST=http://localhost:8088 .travis/run-tests.sh

	PATH=${PHP72_PATH} .travis/install-nginx.sh
	PATH=${PHP72_PATH} .travis/setup-typo3.sh typo3/minimal:^9.5.0 typo3/cms-adminpanel:^9.5.0
	HOST=http://localhost:8088 PATH=${PHP72_PATH} .travis/run-tests.sh

	pkill -F /tmp/php-fpm.pid
	pkill -F /tmp/nginx.pid
	rm -rf .travis/nginx/

t3x-pack:
	git archive -o $(EXTKEY)_`git describe --always --tags`.zip HEAD
