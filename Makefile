## Variables
BIN_DIR = vendor/bin
PHPCS = $(BIN_DIR)/phpcs
PHPCBF = $(BIN_DIR)/phpcbf
PHPSTAN = $(BIN_DIR)/phpstan
PHPUNIT = $(BIN_DIR)/phpunit

## Commands
DOCKER_RUN = docker run --tty --interactive --rm --volume="$$PWD":/opt/phalcon-repository --workdir=/opt/phalcon-repository phalcon-repository

.PHONY: default analyse test validate phpstan phpstan-src phpstan-tests test test-unit

## Default command, runs all checks (use `make`)
default: analyse test

## Build docker image and store inspection into phalcon-repository.json file
dev/phalcon-repository.json: dev/Dockerfile
	docker build --tag=phalcon-repository dev/
	docker image inspect phalcon-repository >> dev/phalcon-repository.json

## Ensure vendor folder exists
vendor: composer.json dev/phalcon-repository.json
	$(DOCKER_RUN) composer install --no-suggest --no-interaction

## Analyse stage
analyse: validate cs-check phpstan

## Validates composer.json file
validate: composer.json dev/phalcon-repository.json
	$(DOCKER_RUN) composer validate --strict

## PHP static analysis
phpstan: phpstan-src phpstan-tests

## PHP static analysis for src
phpstan-src: vendor phpstan-src.neon.dist
	$(DOCKER_RUN) $(PHPSTAN) analyse --level=max --configuration=phpstan-src.neon.dist src

## PHP static analysis for tests
phpstan-tests: vendor phpstan-tests.neon.dist
	$(DOCKER_RUN) $(PHPSTAN) analyse --level=max --configuration=phpstan-tests.neon.dist tests/unit

## Code sniffing (cs check)
cs-check: vendor
	$(PHPCS)

## Apply code sniffing rules (cs fixer)
cs-fix: vendor
	$(PHPCBF)

## All tests
test: test-unit

## Unit tests
test-unit: vendor phpunit.xml.dist
	$(DOCKER_RUN) $(PHPUNIT) --configuration=phpunit.xml.dist --testsuite=Unit

############################################################################################
## HELPERS
############################################################################################

.PHONY: docker-enter

## Enter inside the docker container
docker-enter: dev/phalcon-repository.json
	$(DOCKER_RUN) /bin/bash
