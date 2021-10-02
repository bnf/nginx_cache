EXTKEY = $(notdir $(shell pwd))

# Imply php 7.2 is installed from http://rpms.remirepo.net/ (for Fedora/CentOS)
# needs to be adapted if you're on Debian/Ubuntu
PHP70_PATH = "/opt/remi/php70/root/usr/sbin/:/opt/remi/php70/root/usr/bin/:${PATH}"
PHP72_PATH = "/opt/remi/php72/root/usr/sbin/:/opt/remi/php72/root/usr/bin/:${PATH}"
PHP74_PATH = "/opt/remi/php74/root/usr/sbin/:/opt/remi/php74/root/usr/bin/:${PATH}"

.build/assert-1.1.sh:
	wget https://raw.github.com/lehmannro/assert.sh/v1.1/assert.sh -O $@
	ln -snf assert-1.1.sh .build/assert.sh


check: .build/assert-1.1.sh
	PATH=${PHP70_PATH} .travis/install-nginx.sh
	PATH=${PHP70_PATH} COMPSER_VERSION= .build/setup-typo3.sh typo3/cms:^7.6
	HOST=http://localhost:8088 .build/run-tests.sh

	PATH=${PHP70_PATH} .travis/install-nginx.sh
	PATH=${PHP70_PATH} COMPOSER_VERSION= .build/setup-typo3.sh typo3/cms:~8.7.0
	HOST=http://localhost:8088 .build/run-tests.sh

	PATH=${PHP72_PATH} .travis/install-nginx.sh
	PATH=${PHP72_PATH} COMPOSER_VERSION=2 .build/setup-typo3.sh typo3/minimal:^9.5.0 typo3/cms-adminpanel:^9.5.0
	HOST=http://localhost:8088 PATH=${PHP72_PATH} .build/run-tests.sh

	PATH=${PHP72_PATH} .travis/install-nginx.sh
	PATH=${PHP72_PATH} COMPOSER_VERSION=2 .build/setup-typo3.sh typo3/minimal:^10.4.0 typo3/cms-adminpanel:^10.4.0
	HOST=http://localhost:8088 PATH=${PHP72_PATH} .build/run-tests.sh

	PATH=${PHP74_PATH} .travis/install-nginx.sh
	PATH=${PHP74_PATH} COMPOSER_VERSION=2 .build/setup-typo3.sh typo3/minimal:^11.4.0 typo3/cms-adminpanel:^11.4.0
	HOST=http://localhost:8088 PATH=${PHP74_PATH} .build/run-tests.sh

	pkill -F /tmp/php-fpm.pid
	pkill -F /tmp/nginx.pid
	rm -rf .travis/nginx/

check-with-ddev:
	$(MAKE) ddev-7
	$(MAKE) ddev-8
	$(MAKE) ddev-9
	$(MAKE) ddev-10
	$(MAKE) ddev-11

ddev-7: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mariadb-version=""
	ddev config --mariadb-version="" --mysql-version=5.5 --project-type typo3
	ddev start
	ddev exec composer self-update --1
	ddev exec .build/setup-typo3.sh typo3/cms:^7.6
	ddev exec .build/run-tests.sh

ddev-8: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mysql-version=""
	ddev config --mysql-version="" --mariadb-version=10.3 --project-type typo3
	ddev start
	ddev exec composer self-update --1
	ddev exec .build/setup-typo3.sh typo3/cms:~8.7.0
	ddev exec .build/run-tests.sh

ddev-9: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mysql-version=""
	ddev config --mysql-version="" --mariadb-version=10.3 --project-type typo3
	ddev start
	ddev exec composer self-update --2
	ddev exec .build/setup-typo3.sh typo3/minimal:^9.5.0 typo3/cms-adminpanel:^9.5.0
	ddev exec .build/run-tests.sh

ddev-10: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mysql-version=""
	ddev config --mysql-version="" --mariadb-version=10.3 --project-type typo3
	ddev start
	ddev exec composer self-update --2
	ddev exec .build/setup-typo3.sh typo3/minimal:^10.4.0 typo3/cms-adminpanel:^10.4.0
	ddev exec .build/run-tests.sh

ddev-11: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mysql-version=""
	ddev config --mysql-version="" --mariadb-version=10.3 --project-type typo3
	ddev start
	ddev exec composer self-update --2
	ddev exec .build/setup-typo3.sh typo3/minimal:^11.4.0 typo3/cms-adminpanel:^11.4.0
	ddev exec .build/run-tests.sh

t3x-pack:
	git archive --worktree-attributes -o $(EXTKEY)_`git describe --always --tags`.zip HEAD
