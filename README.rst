NGINX Cache Manager for TYPO3
=============================

.. image:: https://travis-ci.org/qbus-agentur/nginx_cache.svg?branch=master
   :target: https://travis-ci.org/qbus-agentur/nginx_cache

This TYPO3 extensions adds the required bits to use NGINX's fastcgi_cache for TYPO3 pages.
It adds appropriate cache control headers, documents the required NGINX configuration
and flushes the cache when content changes.

Configuration
------------

Just install the extension, no configuration in TYPO3 needed.

.. code-block:: bash

    typo3/cli_dispath.phpsh extbase extension:install nginx_cache
    # Fedora (RPM)
    sudo dnf install nginx nginx-mod-http-perl perl-Digest-MD5
    # Debian (dpkg)
    sudo apt install nginx libdigest-md5-file-perl libnginx-mod-http-perl

Afterwards you need to configure NGINX.

NGINX configuration
*******************
You'll need to configure NGINX to cache the output of the php call.
Therefore you need to specify a fastcgi cache path in the http section
(that is outside your server section):

.. code-block:: nginx

    http {
        fastcgi_cache_path /var/nginx/cache/TYPO3 levels=1:2 keys_zone=TYPO3:10m inactive=24h;

        # We suggest to copy the path to /etc/nginx/perl/lib, to prevent attackers (that use
        # TYPO3 security issues) to inject a new purge.pm, and thus get access to e.g.
        # your https keys (That means on EXT:nginx_cache major update you need to check
        # for changes. Promise: We'll do that for major upgrades only).
	# So use:
        #perl_modules /etc/nginx/perl/lib;
        # ..instead of:
        perl_modules /path/to/your/webroot/typo3conf/ext/nginx_cache/Resources/Private/nginx_purge;

        perl_require purge.pm;
    }

And in your php location you need to configure the desired cache behaviour:

.. code-block:: nginx

    error_page 405 = @purge;
    if ($request_method = PURGE) {
        return 405;
    }

    location ~ \.php$ {
        include fastcgi.conf;
        fastcgi_pass php-fpm;

        fastcgi_cache TYPO3;
        # Use the current request url as cache key
        fastcgi_cache_key $scheme://$host$request_uri;
        # Only cache GET/HEAD requests (not POST or PUT)
        fastcgi_cache_methods GET HEAD;
        # Do not deliver cached content for logged in be users, or requests with a query string
        fastcgi_cache_bypass $cookie_be_typo_user $args;
        # Consider every request non-cacheable – cachability is explicitly defined through TYPO3
        # (using X-Accel-Expires header)
        fastcgi_cache_valid 0;
        # Ignore all cache headers, besides X-Accel-Expires
        fastcgi_ignore_headers "Expires" "Cache-Control" "Vary";

        # For debugging only
        add_header X-Cache  $upstream_cache_status;
    }

    location @purge {
        allow 127.0.0.1;
        # Depending on your servers setup (e.g. if your server is NATed to the public ip, our your fastcgi
        # server is on another ip) you may also need to define the allowed purge ip's here
        # If the cache flush fails with 127.0.0.1 fails, you can find those information in
        # the nginx error log when clearing the cache. Open a shell and execute:
        #   tail -f /var/log/nginx/error.log"
        # ..and perform a frontend cache flush. You should see errors like:
        #   access forbidden by rule, client: YY.YYY.YYY.YY, server: www.example.com, request: "PURGE / HTTP/1.1"
        # In that case add the listed ip here
        #allow YY.YYY.YYY.YY;
        deny all;

        set $purge_path "/var/nginx/cache/TYPO3";
        set $purge_levels "1:2";
        set $purge_cache_key "$scheme://$host$request_uri";
        if ($request_uri = /*) {
            set $purge_all 1;
        }

        perl NginxCache::Purge::handler;
    }

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

Make sure you have the right timezone set, or the cache may invalidate too late.
(Though that applies to TYPO3 Core as well). /etc/php.ini:

.. code-block:: ini

    date.timezone = "Europe/Berlin"


Advantages over nc_staticfilecache
----------------------------------

- Headers can be cached (config.additionalHeaders)
- We have a testsuite running on travis-ci
- Performant support for starttime/endtime (as long as TYPO3 does not fail to calculate the correct cache time)
  (to be fair: nc_staticfilecache provides that through auto-generated .htaccess files,
  but only for apache, not for nginx)
