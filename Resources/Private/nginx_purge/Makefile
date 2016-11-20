all: nginx_purge setuid

.PHONY: setuid

nginx_purge: nginx_purge.c
	gcc -Wall -pedantic -o nginx_purge nginx_purge.c

setuid: nginx_purge
	sudo chown nginx nginx_purge
	sudo chmod 4750 nginx_purge
