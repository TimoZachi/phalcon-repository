## Variables
BIN_DIR := vendor/bin
PROJECT_NAME := phalcon-repository

PHPCS = $(BIN_DIR)/phpcs
PHPCBF = $(BIN_DIR)/phpcbf
PHPSTAN = $(BIN_DIR)/phpstan
PHPUNIT = $(BIN_DIR)/phpunit
PHPBENCH = dev/phpbench.phar
INFECTION = dev/infection.phar

## Commands
DOCKER_RUN = docker run --tty --interactive --rm --volume="$$PWD":/project --workdir=/project
DOCKER_RUN_72 = $(DOCKER_RUN) $(PROJECT_NAME)-php72
DOCKER_RUN_73 = $(DOCKER_RUN) $(PROJECT_NAME)-php73
DOCKER_RUN_73_BENCHMARK = $(DOCKER_RUN) $(PROJECT_NAME)-php73-benchmark

.PHONY: default analyse validate cs-check cs-fix phpstan phpstan-src phpstan-tests benchmark

## Default command, runs all checks (use `make`)
default: analyse test

## Build docker image and store inspection into $(PROJECT_NAME)-php72.json file
dev/$(PROJECT_NAME)-php72.json: dev/Dockerfile
	docker build --build-arg PHP_MINOR_VERSION=7.2 --tag=$(PROJECT_NAME)-php72 dev/
	docker image inspect $(PROJECT_NAME)-php72 >> dev/$(PROJECT_NAME)-php72.json

## Build docker image and store inspection into $(PROJECT_NAME)-php73.json file
dev/$(PROJECT_NAME)-php73.json: dev/Dockerfile
	docker build --build-arg PHP_MINOR_VERSION=7.3 --tag=$(PROJECT_NAME)-php73 dev/
	docker image inspect $(PROJECT_NAME)-php73 >> dev/$(PROJECT_NAME)-php73.json

## Build docker image and store inspection into $(PROJECT_NAME)-php73.json file
dev/$(PROJECT_NAME)-php73-benchmark.json: dev/Dockerfile
	docker build --build-arg PHP_MINOR_VERSION=7.3 --build-arg INSTALL_XDEBUG=false --tag=$(PROJECT_NAME)-php73-benchmark dev/
	docker image inspect $(PROJECT_NAME)-php73-benchmark >> dev/$(PROJECT_NAME)-php73-benchmark.json

## Ensure vendor folder exists
vendor: composer.json dev/$(PROJECT_NAME)-php73.json
	$(DOCKER_RUN_73) composer update --no-suggest --no-interaction

$(PHPCS): vendor
$(PHPCBF): vendor
$(PHPSTAN): vendor
$(PHPUNIT): vendor

$(PHPBENCH):
	curl -o $(PHPBENCH) https://phpbench.github.io/phpbench/phpbench.phar
	curl -o $(PHPBENCH).pubkey https://phpbench.github.io/phpbench/phpbench.phar.pubkey
	chmod +x $(PHPBENCH)

$(INFECTION):
	curl -o $(INFECTION) -L https://github.com/infection/infection/releases/download/0.13.0/infection.phar
	chmod +x $(INFECTION)

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
phpstan-src: dev/$(PROJECT_NAME)-php73.json $(PHPSTAN) vendor phpstan-src.neon.dist
	$(DOCKER_RUN_73) $(PHPSTAN) analyse --level=max --configuration=phpstan-src.neon.dist src

## PHP static analysis for tests
phpstan-tests: dev/$(PROJECT_NAME)-php73.json $(PHPSTAN) vendor phpstan-tests.neon.dist
	$(DOCKER_RUN_73) $(PHPSTAN) analyse --level=max --configuration=phpstan-tests.neon.dist tests

benchmark: dev/$(PROJECT_NAME)-php73-benchmark.json $(PHPBENCH)
	$(DOCKER_RUN_73_BENCHMARK) $(PHPBENCH) run --progress=dots --report=aggregate

.PHONY: test test-unit-php72 test-unit-php73 test-functional-php72 test-functional-php73 test-mutation

## All tests
test: test-unit-php72 test-unit-php73 test-functional-php72 test-functional-php73 test-mutation

## Unit tests
test-unit-php72: $(PHPUNIT) vendor dev/$(PROJECT_NAME)-php72.json
	$(DOCKER_RUN_72) $(PHPUNIT) --testsuite=Unit

## Unit tests
test-unit-php73: $(PHPUNIT) vendor dev/$(PROJECT_NAME)-php73.json
	$(DOCKER_RUN_73) $(PHPUNIT) --coverage-text --testsuite=Unit

## Functional tests
test-functional-php72: $(PHPUNIT) vendor dev/$(PROJECT_NAME)-php72.json
	$(DOCKER_RUN_72) $(PHPUNIT) --testsuite=Functional

## Functional tests
test-functional-php73: $(PHPUNIT) vendor dev/$(PROJECT_NAME)-php73.json
	$(DOCKER_RUN_73) $(PHPUNIT) --testsuite=Functional

test-mutation: dev/$(PROJECT_NAME)-php73.json $(INFECTION)
	$(DOCKER_RUN_73) $(INFECTION) --show-mutations --test-framework-options="--testsuite=Unit" \
	 	--threads=$(nproc) --min-msi=90 --min-covered-msi=90

############################################################################################
## HELPERS
############################################################################################

.PHONY: docker-attach env-cleanup

## Attaches current cli to the docker container
docker-attach: dev/$(PROJECT_NAME)-php73.json
	$(DOCKER_RUN_73) /bin/bash

## Cleanup images and files
env-cleanup:
	rm -f dev/*.json
	rm -f dev/*.phar*
	docker rmi $(docker images | grep $(PROJECT_NAME) | awk '{print $3}')
