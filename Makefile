EXTKEY = $(notdir $(shell pwd))

.build/assert-1.1.sh:
	wget https://raw.github.com/lehmannro/assert.sh/v1.1/assert.sh -O $@
	ln -snf assert-1.1.sh .build/assert.sh

check:
	$(MAKE) ddev-12


ddev-12: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev start
	ddev exec .build/setup-typo3.sh typo3/cms-core:^12.4.0
	ddev exec .build/run-tests.sh

ddev-main: .build/assert-1.1.sh
	ddev stop --remove-data --omit-snapshot
	ddev start
	ddev exec .build/setup-typo3.sh typo3/cms-core:dev-main@dev typo3/cms-frontend:dev-main@dev typo3/cms-backend:dev-main@dev
	ddev exec .build/run-tests.sh

t3x-pack:
	git archive --worktree-attributes -o $(EXTKEY)_`git describe --always --tags`.zip HEAD
