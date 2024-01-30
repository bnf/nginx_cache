EXTKEY = $(notdir $(shell pwd))

.build/assert-1.1.sh:
	wget https://raw.github.com/lehmannro/assert.sh/v1.1/assert.sh -O $@
	ln -snf assert-1.1.sh .build/assert.sh

check:
	ddev stop --remove-data --omit-snapshot
	ddev start
	$(MAKE) check-12
	ddev restart
	$(MAKE) check-13


check-12: .build/assert-1.1.sh
	ddev clean-db
	ddev exec .build/setup-typo3.sh v12
	ddev exec .build/run-tests.sh v12

check-13: .build/assert-1.1.sh
	ddev clean-db
	ddev exec .build/setup-typo3.sh v13
	ddev exec .build/run-tests.sh v13

check-main: .build/assert-1.1.sh
	ddev clean-db
	ddev exec .build/setup-typo3.sh v13-dev
	ddev exec .build/run-tests.sh v13-dev

t3x-pack:
	git archive --worktree-attributes -o $(EXTKEY)_`git describe --always --tags`.zip HEAD
