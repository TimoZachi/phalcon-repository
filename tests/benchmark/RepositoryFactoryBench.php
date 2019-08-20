<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Benchmark;

use Phalcon\Annotations\Adapter\Memory as MemoryAdapter;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use TZachi\PhalconRepository\RepositoryFactory;
use TZachi\PhalconRepository\Resolver\QueryParameter as QueryParameterResolver;
use TZachi\PhalconRepository\Tests\Mock\Model\Company;

/**
 * @BeforeMethods({"setUp"})
 * @Revs(1000)
 * @Iterations(5)
 */
class RepositoryFactoryBench
{
    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    public function setUp(): void
    {
        $this->repositoryFactory = new RepositoryFactory(new MemoryAdapter(), new QueryParameterResolver());
    }

    public function benchGet(): void
    {
        $this->repositoryFactory->get(Company::class);
    }

    public function benchCreate(): void
    {
        $this->repositoryFactory->create(Company::class);
    }
}
