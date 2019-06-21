## Variables
BIN_DIR = vendor/bin

PHPCS = $(BIN_DIR)/phpcs
PHPCBF = $(BIN_DIR)/phpcbf
PHPSTAN = $(BIN_DIR)/phpstan
PHPUNIT = $(BIN_DIR)/phpunit
PHPBENCH = dev/phpbench.phar

## Commands
DOCKER_RUN = docker run --tty --interactive --rm --volume="$$PWD":/opt/phalcon-repository --workdir=/opt/phalcon-repository
DOCKER_RUN_72 = $(DOCKER_RUN) phalcon-repository-php72
DOCKER_RUN_73 = $(DOCKER_RUN) phalcon-repository-php73

.PHONY: default analyse validate cs-check cs-fix phpstan phpstan-src phpstan-tests benchmark

## Default command, runs all checks (use `make`)
default: analyse test

## Build docker image and store inspection into phalcon-repository-php72.json file
dev/phalcon-repository-php72.json: dev/Dockerfile
	docker build --build-arg PHP_MINOR_VERSION=7.2 --tag=phalcon-repository-php72 dev/
	docker image inspect phalcon-repository-php72 >> dev/phalcon-repository-php72.json

## Build docker image and store inspection into phalcon-repository-php73.json file
dev/phalcon-repository-php73.json: dev/Dockerfile
	docker build --build-arg PHP_MINOR_VERSION=7.3 --tag=phalcon-repository-php73 dev/
	docker image inspect phalcon-repository-php73 >> dev/phalcon-repository-php73.json

## Ensure vendor folder exists
vendor: composer.json dev/phalcon-repository-php73.json
	$(DOCKER_RUN_73) composer update --no-suggest --no-interaction

$(PHPCS): vendor
$(PHPCBF): vendor
$(PHPSTAN): vendor
$(PHPUNIT): vendor

$(PHPBENCH):
	curl -o $(PHPBENCH) https://phpbench.github.io/phpbench/phpbench.phar
	curl -o $(PHPBENCH).pubkey https://phpbench.github.io/phpbench/phpbench.phar.pubkey
	chmod +x $(PHPBENCH)

## Analyse stage
analyse: validate cs-check phpstan benchmark

## Validates composer.json file
validate: composer.json
	composer validate --strict

## Code sniffing (cs check)
cs-check: $(PHPCS)
	$(PHPCS)

## Apply code sniffing rules (cs fixer)
cs-fix: $(PHPCBF)
	$(PHPCBF)

## PHP static analysis
phpstan: phpstan-src phpstan-tests

## PHP static analysis for src
phpstan-src: dev/phalcon-repository-php73.json $(PHPSTAN) vendor phpstan-src.neon.dist
	$(DOCKER_RUN_73) $(PHPSTAN) analyse --level=max --configuration=phpstan-src.neon.dist src

## PHP static analysis for tests
phpstan-tests: dev/phalcon-repository-php73.json $(PHPSTAN) vendor phpstan-tests.neon.dist
	$(DOCKER_RUN_73) $(PHPSTAN) analyse --level=max --configuration=phpstan-tests.neon.dist tests

benchmark: dev/phalcon-repository-php73.json $(PHPBENCH)
	$(DOCKER_RUN_73) $(PHPBENCH) run --progress=dots --report=aggregate

.PHONY: test test-unit-php72 test-unit-php73 test-functional-php72 test-functional-php73

## All tests
test: test-unit-php72 test-unit-php73 test-functional-php72 test-functional-php73

## Unit tests
test-unit-php72: $(PHPUNIT) vendor dev/phalcon-repository-php72.json
	$(DOCKER_RUN_72) $(PHPUNIT) --testsuite=Unit

## Unit tests
test-unit-php73: $(PHPUNIT) vendor dev/phalcon-repository-php73.json
	$(DOCKER_RUN_73) $(PHPUNIT) --coverage-text --testsuite=Unit

## Functional tests
test-functional-php72: $(PHPUNIT) vendor dev/phalcon-repository-php72.json
	$(DOCKER_RUN_72) $(PHPUNIT) --testsuite=Functional

## Functional tests
test-functional-php73: $(PHPUNIT) vendor dev/phalcon-repository-php73.json
	$(DOCKER_RUN_73) $(PHPUNIT) --testsuite=Functional

############################################################################################
## HELPERS
############################################################################################

.PHONY: docker-attach

## Attaches current cli to the docker container
docker-attach: dev/phalcon-repository-php73.json
	$(DOCKER_RUN_73) /bin/bash
