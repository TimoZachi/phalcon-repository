# Phalcon Repository

Phalcon Repository is a library for implementing the repository pattern in projects that use the Phalcon PHP Framework

## Installation

Installation is done via composer: `composer require tzachi/phalcon-repository:1.0.x-dev`

## Usage

Assuming you have a model (that extends Phalcon's Model) called `User`, you can use the repository like this:

```php

use MyProject\Model\User;
use TZachi\PhalconRepository\ModelWrapper;
use TZachi\PhalconRepository\Repository;

$userRepository = new Repository(new ModelWrapper(User::class));

// Simple usage, finds a user model by its id.
$user = $userRepository->findFirst(1);

// Finds a user model by its name, ordering by name ASC.
$user = $userRepository->findFirstBy('name', 'Test User Name', ['name' => 'ASC']);

// A bit more of complexity here, this will find a user using the following condition:
// id BETWEEN 15 AND 21 OR id IN (1, 2, 3) OR (name = 'Timo Zachi' AND created_at > '2019-01-01')
// ordering the results by id DESC.
$user = $userRepository->findFirstWhere(
    [
        '@type' => Repository::TYPE_OR,
        [
            '@operator' => 'BETWEEN',
            'id' => [15, 21], // Between operator.
        ],
        'id' => [1, 2, 3], // In operator.
        [
            // Type AND is default, doesn't need to be specified. It's explicit here for sample purposes.
            '@type' => Repository::TYPE_AND,
            'name' => 'Timo Zachi', // Equals operator (default).
            [
                '@operator' => '>',
                'createdAt' => '2019-01-01',
            ],
        ],
    ],
    ['id' => 'DESC'] // Order by.
);

// You can use the same parameters to query for multiple records, that will return a result set.
// You need only to use one of the methods 'findWhere' or 'findBy'. Notice that there is also a
// limit and offset parameter.
$resultSet = $userRepository->findWhere(
    ['email' => 'timo.zachi@timoteo.me'], // Conditions.
    ['id' => 'DESC'], // Order by.
    10, // Limit.
    5 // Offset.
);

```

## Contributing

Pull requests are most certainly welcome. The code will be validated against the following checks:

* Code is validated against the [Coding Standard](https://github.com/timozachi/coding-standard)
* A static analysis tool (phpstan) will analyse your code
* Unit tests must have 100% code coverage (if you create a new feature, please ensure that there are enough tests to cover it)
* For some features, a functional test is required (soon enough I will push some functional tests for the existent code)

To make things simpler, a Makefile was created. All you have to do is run:
```shell
make
```
To run specific checks:
```shell
make cs-check
make phpstan-src
make phpstan-tests
make test-unit
```

This will run all available checks against your code and inform you of any failed check

Please note that soon enough a CI tool will be available to do those checks

## Notes

Please note that there is still a lot to be done in this project, hopefully by then phalcon will have released
its 4.0 version so that this project will be able to use it
