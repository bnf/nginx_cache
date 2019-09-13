package NginxCache::Purge;

use strict;
use warnings;
use nginx;
use Digest::MD5 qw(md5_hex);
use File::Find;

sub handler {
    my $r = shift;
    my $path = $r->variable('purge_path');
    my $levels = $r->variable('purge_levels');
    my $cache_key = $r->variable('purge_cache_key');

    if ($r->request_method ne 'PURGE') {
        return DECLINED;
    }
    $r->send_http_header('text/plain');

    if ($r->variable('purge_all') == 1) {
        # Make extra sure we don't delete the entire system (or at least those files writable by nginx)
        # in case of a possible configuration mistake (e.g. if $path is empty),
        # by searching for filenames with 32 chars.
        # TODO: Despite our 32 length check we should check $path to be something sensible
        File::Find::find(\&delete, $path);
        $r->print("Removed all cache files.\n");
        return OK;
    }

    my $digest = md5_hex($cache_key);
    my @levels = split(':', $levels);
    my $offset = 0;
    foreach my $level (@levels) {
        $offset += $level;
        $path .= '/' . substr($digest, -$offset, $level);
    }

    my $cachefile = $path . '/' . $digest;
    if (-f $cachefile) {
        unlink $cachefile;
        $r->print("Removed cache file ", $cachefile, ".\n");
    } else {
        $r->print("Cache file ", $cachefile, " for cache_key: ", $cache_key, " not found.\n");
    }

    return OK;
}

sub delete {
    -f && length == 32 && unlink
}

1;
__END__

## Example NGINX configuration

#http {
#    proxy_cache_path /tmp/cache1 levels=1:2 keys_zone=cache1:10m max_size=100m inactive=60m;
#    perl_modules /path/to/this/script;
#    perl_require purge.pm;
#
#    server {
#        #listen ..;
#        #server_name ..;
#
#        # Example NGINX configuration
#        location @upstream {
#            proxy_pass http://dev;
#            proxy_cache cache1;
#        }
#        location @purge {
#            allow 127.0.0.1;
#            deny all;
#
#            set $purge_path "/tmp/cache1";
#            set $purge_levels "1:2";
#            set $purge_cache_key "http://dev$request_uri";
#            set $purge_all 0;
#            if ($request_uri = /*) {
#                set $purge_all 1;
#            }
#            perl NginxCache::Purge::handler;
#        }
#
#        location / {
#            error_page 405 = @purge;
#            if ($request_method = PURGE) {
#                return 405;
#            }
#            try_files $uri $uri/ @upstream;
#        }
#    }
#}
