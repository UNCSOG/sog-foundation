# Tutorial: http://www.cs.colby.edu/maxwell/courses/tutorials/maketutor/
# Docs: https://www.gnu.org/software/make/

## Help
.PHONY: help readme

PLATFORM :=

ifeq ($(OS),Windows_NT)
	PLATFORM := windows
else
	UNAME_S := $(shell uname -s)
ifeq ($(UNAME_S),Linux)
	PLATFORM := linux
else
ifeq ($(UNAME_S),Darwin)
	PLATFORM := osx
endif
endif
endif

help:
	$(info -=* Welcome to a Toolset Makefile *=-)
	$(info )
	$(info You can use one of these commands:)
	$(info )
	$(info > `make install` to install the dependencies for production)
	$(info - `make setup` first time to configure the repository for development)
	$(info - `make dev` to install the dependencies for development)
	$(info - `make test` to run automated tests)
	$(info - `make classmap` to recreate PHP autoloader classmaps (Zend framework required))
	$(info - `make update-version ver=X.X.X` to update the library version in the main library file)
	$(info - `make pre-release ver=X.X.X` to prepare the library for a release)
	$(info - `make update` to update composer and npm dependencies)
	$(info - `make update-auryn` to update the embedded auryn library)
	$(info - `make version-bump` to increment the \$toolset_common_version value by 1)
	$(info )
	$(info You will need Composer (https://getcomposer.org/download/) and npm (https://nodejs.org/) for this to work properly.)
	$(info )
	$(info For more information, run `make readme`.)
	$(info )
	$(info Detected platform: $(PLATFORM).)
	$(info )
	@ls > /dev/null

readme:
	cat README.md

## Higher-order targets and shortcuts
.PHONY: dev install setup

dev: composer-install
dev: npm-ci
dev: msg-completed

install: composer-install-prod
install: msg-completed

setup:: githooks
setup:: dev
setup:: msg-completed

## Install git hooks
.PHONY: remove-githooks install-githooks githooks

githooks: remove-githooks
githooks: install-githooks

remove-githooks:
ifeq ($(PLATFORM),windows)
	@mkdir -p ".git/hooks"
	@find .git/hooks -type l -exec rm {} \;
else
	mkdir -p .git/hooks
	@find .git/hooks -type l -exec rm {} \;
endif

install-githooks:
ifdef CI
	$(info Skipping Git Hooks in CI)
else
ifeq ($(PLATFORM),windows)
	cp .githooks/* .git/hooks/
	$(info Looks like you are on Windows... files copied.)
else
	@mkdir -p .git/hooks
	@find .githooks -type f -exec ln -sf ../../{} .git/hooks/ \;
	$(info Git Hooks installed)
endif
endif

## Composer
.PHONY: composer-install composer-install-prod

composer-install:
	$(info Installing Composer dependencies)
	composer install

composer-install-prod:
	$(info Installing Composer dependencies)
	@composer --no-dev install

## NPM
.PHONY: npm-ci

npm-ci:
	$(info Installing Node dependencies (CI mode))
	@npm ci

## Just Messages
.PHONY: msg-completed

msg-completed:
	$(info )
	$(info Make operation complete. Have Fun!)
	$(info )

## Precommit
.PHONY: precommit check-duplicates check-compatibility validate-composer

precommit:: validate-composer
precommit:: check-duplicates
precommit:: check-compatibility
precommit:: version-bump

check-duplicates: composer-install
	./.make/check-duplicates.sh

check-compatibility: composer-install
	./.make/check-compatibility.sh

validate-composer: composer-install
	./.make/check-composer.sh

## Tests
.PHONY: test phpunit

test:: composer-install
test:: phpunit

phpunit::
	$(info Running PhpUnit)
	"vendor/bin/phpunit" --fail-on-warning --stop-on-failure

## Classmap
.PHONY: classmap

classmap::
	./.make/recreate_classmap.sh

## Version update
.PHONY: update-version

update-version::
	@node ./.make/deploy-update-version.js -p ./.make/deploy-update-version-patterns.json -r $(ver)
	@node ./.make/deploy-update-tcl-version-number.js -r $(ver)

## Pre-release
.PHONY: pre-release

pre-release:: update
pre-release:: update-version
pre-release:: classmap
pre-release:: msg-completed

## Update
.PHONY: update

update::
	composer update
	npm install

## Auryn
update-auryn::
	./.make/update_auryn.sh


## $toolset_common_version
.PHONY: version-bump bump-version

version-bump::
	@node ./.make/deploy-update-tcl-version-number.js --bump

bump-version:: version-bump
