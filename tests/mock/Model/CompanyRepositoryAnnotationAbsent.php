<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Mock\Model;

use Phalcon\Mvc\Model;

/**
 * Model for RepositoryFactoryTest. This model does not specify a repository.
 *
 * @AnAnnotation
 * @AnotherAnnotation(abc=cde)
 */
class CompanyRepositoryAnnotationAbsent extends Model
{
}
