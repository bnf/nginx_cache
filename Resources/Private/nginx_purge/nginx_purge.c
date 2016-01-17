#define CACHE_PATH "/var/nginx/cache/TYPO3/"

#define _XOPEN_SOURCE 700
#include <stdio.h>
#include <stdlib.h>
#include <errno.h>
#include <unistd.h>
#include <ftw.h>
#include <sys/types.h>
#include <dirent.h>

static int
unlink_cb(const char *fpath, const struct stat *sb, int typeflag, struct FTW *ftwbuf)
{
	int rv = remove(fpath);

	/* Do not remove the root directory */
	if (ftwbuf->level == 0)
		return 0;

	if (rv)
		perror(fpath);

	return rv;
}

static int
rmrf(char *path)
{
	return nftw(path, unlink_cb, 64, FTW_DEPTH | FTW_PHYS);
}

int
main(int argc, char *argv[])
{
	DIR *path;
	int fd;
	int ret;

	if (argc == 1)
		return -rmrf(CACHE_PATH);

	if (argc < 2)
		exit(1);

	path = opendir(CACHE_PATH);
	if (!path)
		exit(2);

	fd = dirfd(path);
	if (fd < 0) {
		perror(argv[1]);
		exit(3);
	}

	ret = unlinkat(fd, argv[1], 0);
	if (ret < 0) {
		if (errno == ENOENT) {
			/* It's not an error if unlinkat fails, since that file */
			exit(0);
		}
		perror(argv[1]);
	}

	return 0;
}
