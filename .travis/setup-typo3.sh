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
composer require "$TYPO3_VERSION" $TYPO3_EXTRA_DEPENDENCIES
#Restore composer.json (was changed by the prior composer require)
git checkout -- composer.json

export TYPO3_PATH_WEB=$PWD/.build/public

.build/bin/typo3cms install:setup --non-interactive --database-user-name="root" $PWARG --database-host-name="localhost" --database-port="3306" --database-name="nginx_cache_travis_test" --admin-user-name="admin" --admin-password="password" --site-name="Travis Install" --site-setup-type="createsite"
.build/bin/typo3cms configuration:set SYS/displayErrors 1
.build/bin/typo3cms configuration:set SYS/systemLog error_log
.build/bin/typo3cms configuration:set SYS/systemLogLevel 0
.build/bin/typo3cms extension:activate cache_status
.build/bin/typo3cms extension:activate nginx_cache
