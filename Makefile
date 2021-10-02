EXTKEY = $(notdir $(shell pwd))

.build/assert-1.1.sh:
	wget https://raw.github.com/lehmannro/assert.sh/v1.1/assert.sh -O $@
	ln -snf assert-1.1.sh .build/assert.sh

check:
	$(MAKE) ddev-10
	$(MAKE) ddev-11

ddev-10: .build/assert-1.1.sh
	ddev start
	ddev exec .build/setup-typo3.sh typo3/minimal:^10.4.0 typo3/cms-adminpanel:^10.4.0
	ddev exec .build/run-tests.sh

ddev-11: .build/assert-1.1.sh
	ddev start
	ddev exec .build/setup-typo3.sh typo3/minimal:^11.4.0 typo3/cms-adminpanel:^11.4.0
	ddev exec .build/run-tests.sh

t3x-pack:
	git archive --worktree-attributes -o $(EXTKEY)_`git describe --always --tags`.zip HEAD
