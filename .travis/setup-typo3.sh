#!/bin/bash

# Default to TYPO3 9.5 LTS
export TYPO3_VERSION=${1:-typo3/minimal:^9.5}
export TYPO3_EXTRA_DEPENDENCIES=${2}
export DBNAME=nginx_cache_travis_test
[[ -f $HOME/.my.cnf ]] && DBPASS=$(php -r "\$d = @parse_ini_file('$HOME/.my.cnf'); if (isset(\$d['password'])) echo \$d['password'];") || DBPASS=""
[[ -n "$DBPASS" ]] && export PWARG="--database-user-password=$DBPASS" || export PWARG=""

set -ve

rm -rf .build/
echo "DROP DATABASE IF EXISTS $DBNAME;" | mysql -u root

rm -f composer.lock
echo "$TYPO3_VERSION"
echo "$TYPO3_EXTRA_DEPENDENCIES"
composer${COMPOSER_VERSION} require "$TYPO3_VERSION" $TYPO3_EXTRA_DEPENDENCIES
#Restore composer.json (was changed by the prior composer require)
git checkout -- composer.json

export TYPO3_PATH_WEB=$PWD/.build/public

if grep -C2 '"name": "helhum/typo3-console"' .build/vendor/composer/installed.json  | grep '"version": "4' >/dev/null
then
	.build/vendor/bin/typo3cms install:setup --non-interactive --database-user-name="root" $PWARG --database-host-name="localhost" --database-port="3306" --database-name="nginx_cache_travis_test" --admin-user-name="admin" --admin-password="password" --site-name="Travis Install" --site-setup-type="createsite"
else
	echo "CREATE DATABASE $DBNAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql -u root

	export TYPO3_INSTALL_DB_DRIVER=mysqli
	export TYPO3_INSTALL_DB_USER=root
	export TYPO3_INSTALL_DB_PASSWORD=$DBPASS
	export TYPO3_INSTALL_DB_HOST=localhost
	export TYPO3_INSTALL_DB_PORT=3306
	export TYPO3_INSTALL_DB_DBNAME=$DBNAME
	export TYPO3_INSTALL_DB_UNIX_SOCKET=''
	export TYPO3_INSTALL_DB_USE_EXISTING=y
	export TYPO3_INSTALL_ADMIN_USER=admin
	export TYPO3_INSTALL_ADMIN_PASSWORD=password
	export TYPO3_INSTALL_SITE_NAME="Travis Install"
	export TYPO3_INSTALL_SITE_SETUP_TYPE="site"
	export TYPO3_INSTALL_SITE_BASE_URL="/"
	export TYPO3_INSTALL_WEB_SERVER_CONFIG=none

	.build/vendor/bin/typo3cms install:setup
fi

mkdir -p $PWD/.build/config/sites/main
cat > $PWD/.build/config/sites/main/config.yaml <<EOF
base: '/'
errorHandling: {  }
languages:
  -
    title: English
    enabled: true
    languageId: 0
    base: /
    typo3Language: default
    locale: en_US.UTF-8
    iso-639-1: en
    navigationTitle: English
    hreflang: en-us
    direction: ltr
    flag: us
rootPageId: 1
routes: {  }
EOF
.build/vendor/bin/typo3cms cache:flush
.build/vendor/bin/typo3cms configuration:set SYS/displayErrors 1
test -f .build/public/typo3conf/PackageStates.php && .build/vendor/bin/typo3cms extension:activate cache_status || true
test -f .build/public/typo3conf/PackageStates.php && .build/vendor/bin/typo3cms extension:activate nginx_cache || true
