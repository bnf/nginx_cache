#!/bin/bash

set -ve

rm -rf \
       	.build/config/sites/ \
	.build/config/system/settings.php \
	.build/composer.lock \
       	.build/public/ \
	.build/vendor/

cd .build/
composer install

TYPO3_DB_USERNAME=db \
TYPO3_DB_PORT=3306 \
TYPO3_DB_HOST=db \
TYPO3_DB_DBNAME=db \
TYPO3_DB_PASSWORD=db \
TYPO3_DB_DRIVER=mysqli \
TYPO3_SETUP_ADMIN_EMAIL=admin@example.com \
TYPO3_SETUP_ADMIN_USERNAME=admin \
TYPO3_SETUP_ADMIN_PASSWORD='Pa$$w0rd' \
TYPO3_SETUP_CREATE_SITE="${DDEV_PRIMARY_URL}/" \
TYPO3_PROJECT_NAME="NGINX Cache Test Install" \
vendor/bin/typo3 setup --force

# FIXME: currently required, because requesting ${DDEV_PRIMARY_URL}/
# from inside the container returns 404 site not found
sed -i '/^base:/s/^base.*$/base: \//' config/sites/main/config.yaml

vendor/bin/typo3 cache:flush
