ARG BASE_IMAGE
FROM $BASE_IMAGE
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y -o Dpkg::Options::="--force-confold" --no-install-recommends --no-install-suggests nginx-extras libnginx-mod-http-perl libdigest-md5-file-perl make
RUN sed -i \
	-e '1i include /usr/share/nginx/modules-available/mod-http-perl.conf;' \
	-e '/http {/a perl_modules /var/www/html/Resources/Private/nginx_purge; perl_require purge.pm;' \
	-e '/http {/a fastcgi_cache_path /tmp/cache levels=1:2 keys_zone=TYPO3:10m inactive=24h;' \
	/etc/nginx/nginx.conf
RUN composer self-update --2
