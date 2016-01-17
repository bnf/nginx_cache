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

Flush Helper compilation
************************
This extension includes a helper programm to clear the nginx cache.

Background: Nginx stores the cache files with a 600 file mode. That forbids
the php process to remove the cache if run under another user than nginx.

This helper programm needs to be compiled, and configured as setuid
programm launched as nginx user.
We're using a C programm, since scripts can't be used with the setuid bit.
Note: You'll be prompted for a sudo password to be able to set
the setuid bit and the owner.

Use the provided Makefile to launch the compiler:

.. code-block:: bash

    cd typo3conf/ext/nginx_cache/Resources/Private/nginx_purge
    # You sudo password will be requested for the setuid operation
    make

Known Issues
------------

- The admin panel is rendered into the cache, if enabled
- Frontend editing not tested – probably not working because edit icons will be cached

Those issues can currently be avoided by using a config.no_cache=1 hack for logged in backend users:

.. code-block:: txt

    [globalVar = TSFE:beUserLogin = 1]
        config.no_cache = 1
    [global]
