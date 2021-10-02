#!/bin/bash

# Default to TYPO3 9.5 LTS
export TYPO3_VERSION=${1:-typo3/minimal:^9.5}
export TYPO3_EXTRA_DEPENDENCIES=${2}

if [ -z ${IS_DDEV_PROJECT+x} ]; then
	DBHOST=localhost
	DBNAME=nginx_cache_test_db
	[[ -f $HOME/.my.cnf ]] && DBPASS=$(php -r "\$d = @parse_ini_file('$HOME/.my.cnf'); if (isset(\$d['password'])) echo \$d['user'];") || DBUSER="root"
	[[ -f $HOME/.my.cnf ]] && DBPASS=$(php -r "\$d = @parse_ini_file('$HOME/.my.cnf'); if (isset(\$d['password'])) echo \$d['password'];") || DBPASS=""
	[[ -n "$DBPASS" ]] && export PWARG="--database-user-password=$DBPASS" || export PWARG=""
else
	DBHOST=db
	DBNAME=db
	DBUSER=db
	DBPASS=db
	PWARG="--database-user-password=db"
fi

set -ve

rm -rf composer.lock .build/public/typo3conf/LocalConfiguration.php .build/public/typo3conf/PackageStates.php .build/vendor/ .build/public/typo3/ .build/public/index.php .build/public/typo3conf/ext/

echo "DROP DATABASE IF EXISTS $DBNAME;" | mysql -u $DBUSER -p$DBPASS --database ''

echo "$TYPO3_VERSION"
echo "$TYPO3_EXTRA_DEPENDENCIES"
composer${COMPOSER_VERSION} require "$TYPO3_VERSION" $TYPO3_EXTRA_DEPENDENCIES
#Restore composer.json (was changed by the prior composer require)
git checkout -- composer.json

export TYPO3_PATH_WEB=$PWD/.build/public

if grep -C2 '"name": "helhum/typo3-console"' .build/vendor/composer/installed.json  | grep '"version": "4' >/dev/null
then
	.build/vendor/bin/typo3cms install:setup --non-interactive --database-user-name="$DBUSER" $PWARG --database-host-name="$DBHOST" --database-port="3306" --database-name="$DBNAME" --admin-user-name="admin" --admin-password="password" --site-name="Test Install" --site-setup-type="createsite"
else
	echo "CREATE DATABASE $DBNAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | mysql -u $DBUSER -p$DBPASS --database ''

	export TYPO3_INSTALL_DB_DRIVER=mysqli
	export TYPO3_INSTALL_DB_USER=$DBUSER
	export TYPO3_INSTALL_DB_PASSWORD=$DBPASS
	export TYPO3_INSTALL_DB_HOST=$DBHOST
	export TYPO3_INSTALL_DB_PORT=3306
	export TYPO3_INSTALL_DB_DBNAME=$DBNAME
	export TYPO3_INSTALL_DB_UNIX_SOCKET=''
	export TYPO3_INSTALL_DB_USE_EXISTING=y
	export TYPO3_INSTALL_ADMIN_USER=admin
	export TYPO3_INSTALL_ADMIN_PASSWORD=password
	export TYPO3_INSTALL_SITE_NAME="Test Install"
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
