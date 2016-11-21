NGINX Cache Manager for TYPO3
=============================
This TYPO3 extensions adds the required bits to use NGINX's fastcgi_cache for TYPO3 pages.
It adds appropriate cache control headers, documents the required NGINX configuration
and flushes the cache when content changes.

Configuration
------------

Just install the extension, no configuration in TYPO3 needed.

.. code-block:: bash

    typo3/cli_dispath.phpsh extbase extension:install nginx_cache

Though you'll need to configure NGINX and compile a cache flush helper, see below.

NGINX configuration
*******************
You'll need to configure NGINX to cache the output of the php call.
Therefor you need to configure a fastcgi cache path in the http section
(that is outside your sever section):

.. code-block:: nginx

    http {
        fastcgi_cache_path /var/nginx/cache/TYPO3 levels=1:2 keys_zone=TYPO3:10m inactive=24h;

        # We suggest to copy the path to /etc/nginx/perl/lib, to prevent attackers (that use
        # TYPO3 security issues) to inject a new purge.pm, and thus get access to e.g.
        # your https keys (That means on EXT:nginx_cache major update you need to check
        # for changes. Promise: We'll do that for major upgrades only).
        #perl_modules /etc/nginx/perl/lib;
        # instead of
        perl_modules /path/to/your/webroot/typo3conf/ext/nginx_cache/Resources/Private/nginx_purge;

        perl_require purge.pm;
    }

And in your php location you need to configure the desired cache behaviour:

.. code-block:: nginx

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
        error_page 405 = @purge;
        if ($request_method = PURGE) {
            return 405;
        }
        try_files $uri $uri/ /index.php$is_args$args;
    }
