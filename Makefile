EXTKEY = $(notdir $(shell pwd))

.build/assert-1.1.sh:
	wget https://raw.github.com/lehmannro/assert.sh/v1.1/assert.sh -O $@
	ln -snf assert-1.1.sh .build/assert.sh

check:
	$(MAKE) ddev-7
	$(MAKE) ddev-8
	$(MAKE) ddev-9
	$(MAKE) ddev-10
	$(MAKE) ddev-11

ddev-7: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mariadb-version=""
	ddev config --mariadb-version="" --mysql-version=5.5 --project-type typo3
	ddev start
	ddev exec composer self-update --1
	ddev exec .build/setup-typo3.sh typo3/cms:^7.6
	ddev exec .build/run-tests.sh

ddev-8: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mysql-version=""
	ddev config --mysql-version="" --mariadb-version=10.3 --project-type typo3
	ddev start
	ddev exec composer self-update --1
	ddev exec .build/setup-typo3.sh typo3/cms:~8.7.0
	ddev exec .build/run-tests.sh

ddev-9: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mysql-version=""
	ddev config --mysql-version="" --mariadb-version=10.3 --project-type typo3
	ddev start
	ddev exec composer self-update --2
	ddev exec .build/setup-typo3.sh typo3/minimal:^9.5.0 typo3/cms-adminpanel:^9.5.0
	ddev exec .build/run-tests.sh

ddev-10: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mysql-version=""
	ddev config --mysql-version="" --mariadb-version=10.3 --project-type typo3
	ddev start
	ddev exec composer self-update --2
	ddev exec .build/setup-typo3.sh typo3/minimal:^10.4.0 typo3/cms-adminpanel:^10.4.0
	ddev exec .build/run-tests.sh

ddev-11: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev config --mysql-version=""
	ddev config --mysql-version="" --mariadb-version=10.3 --project-type typo3
	ddev start
	ddev exec composer self-update --2
	ddev exec .build/setup-typo3.sh typo3/minimal:^11.4.0 typo3/cms-adminpanel:^11.4.0
	ddev exec .build/run-tests.sh

t3x-pack:
	git archive --worktree-attributes -o $(EXTKEY)_`git describe --always --tags`.zip HEAD
