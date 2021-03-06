# Phalcon Repository

[![License](https://img.shields.io/packagist/l/tzachi/phalcon-repository.svg)](https://packagist.org/packages/tzachi/phalcon-repository)
[![Total Downloads](https://img.shields.io/packagist/dt/tzachi/phalcon-repository.svg)](https://packagist.org/packages/tzachi/phalcon-repository)
[![Latest Stable Version](https://img.shields.io/packagist/v/tzachi/phalcon-repository.svg?color=blue&label=stable)](https://packagist.org/packages/tzachi/phalcon-repository)
[![Unstable Version](https://img.shields.io/packagist/vpre/tzachi/phalcon-repository.svg?label=unstable)](https://packagist.org/packages/tzachi/phalcon-repository)

![Branch master](https://img.shields.io/badge/branch-master-brightgreen.svg?style=flat-square)
[![Build Status](https://travis-ci.org/TimoZachi/phalcon-repository.svg?branch=master)](https://travis-ci.org/TimoZachi/phalcon-repository)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/TimoZachi/phalcon-repository/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/TimoZachi/phalcon-repository/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/TimoZachi/phalcon-repository/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/TimoZachi/phalcon-repository/?branch=master)

Phalcon Repository is a library for implementing the repository pattern in projects that use the Phalcon PHP Framework

## Installation

Installation is done via composer: `composer require tzachi/phalcon-repository:0.1.x-dev`

## Usage

Assuming you have a model (that extends Phalcon's Model) called `User`, you can use the repository like this:

```php

use MyProject\Model\User;
use TZachi\PhalconRepository\ModelWrapper;
use TZachi\PhalconRepository\Repository;
use TZachi\PhalconRepository\Resolver\QueryParameter;

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
        '@type' => QueryParameter::TYPE_OR,
        [
            '@operator' => 'BETWEEN',
            'id' => [15, 21], // Between operator.
        ],
        'id' => [1, 2, 3], // In operator.
        [
            // Type AND is default, doesn't need to be specified. It's explicit here for sample purposes.
            '@type' => QueryParameter::TYPE_AND,
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

// Aggregation methods

// Count number of users in table
$userRepository->count();

// Get the minimum value of the `createdAt` colum using a where condition
$userRepository->minimum('createdAt', ['id' => [100, 200], '@operator' => 'BETWEEN']);
// Get the maximum value of the `name` column in the entire table
$userRepository->maximum('name');

// Returns the sum of the balance column on users with id 40, 41 and 42
$userRepository->sum('balance', ['id' => [40, 41, 42]]);

// Returns the average of the balance column on users with id 40, 41 and 42
$userRepository->average('balance', ['id' => [40, 41, 42]]);

```

To set up the repository pattern inside a phalcon project, you can use the `RepositoryFactory` class:

```php

use Phalcon\Annotations\AdapterInterface as AnnotationsAdapterInterface;
use TZachi\PhalconRepository\RepositoryFactory;

// Set the repository service as a shared service
$di->setShared(
    'repositoryManager',
    function (): RepositoryFactory {
        /**
         * @var AnnotationsAdapterInterface $annotations
         */
        $annotations = $this->get('annotations');

        // The repository factory reads the annotations of the Model class to determine which repository it should use,
        // that's why it needs the annotations parser. It falls back to the default repository class if one wasn't
        // specified in the model
        return new RepositoryFactory($annotations);
    }
);

```

Now if you want a specific Repository for a specific Model, first create the repository:

```php

namespace MyApp\Repository;

use TZachi\PhalconRepository\Repository;

class UserRepository extends Repository
{
    public function findLastUserCreated(): User
    {
        return $this->findFirstWhere([], ['id' => 'DESC']);
    }

```

And then, in the model, add the annotation to specify which class should be its repository:

```php
namespace MyApp\Model;

use MyApp\Model\User;
use Phalcon\Mvc\Model;

/**
 * @Repository(MyApp\Repository\UserRepository);
 */
class User extends Model
{
    ...
```

Now anywhere in your project you can easily get the model's repository:

```php
public function userAction($id): void
{
    /**
     * @var \MyApp\Repository\UserRepository $userRepository
     */
    $userRepository = $this->repositoryManager->get(\MyApp\Model\User::class);
    $user = $userRepository->findLastUserCreated($id);
    ...
```

## Contributing

Pull requests are most certainly welcome. The code will be validated against the following checks:

* Code is validated against the [Coding Standard](https://github.com/timozachi/phalcon-repository)
* A static analysis tool (phpstan) will analyse your code
* Unit tests must have 100% code coverage (if you create a new feature, please ensure that there are enough tests to cover it)
* For some features, a functional test is required 

To make things simpler, a Makefile was created. All you have to do is run:
```bash
make
```
The `make` command will run all available checks against your code and inform you if any of them fails

If you want to run specific checks:
```bash
# check code style
make cs-check
# static analysis on src directory
make phpstan-src
# static analysis on tests directory
make phpstan-tests
# run unit tests on php 7.3
make test-unit-php73
# run functional tests on php 7.3
make test-functional-php73
```

To fix cs automatically (not all rules can be fixed automatically):
```bash
make cs-fix
```

## Notes

Please note that there is still a lot to be done in this project and it is still under development. 
Hopefully when its done, phalcon will have released its 4.0 version so that this project will be able to use it
