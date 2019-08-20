<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Functional;

use Phalcon\Annotations\Adapter\Memory as MemoryAdapter;
use RuntimeException;
use TZachi\PhalconRepository\Repository;
use TZachi\PhalconRepository\RepositoryFactory;
use TZachi\PhalconRepository\Resolver\Parameter as ParameterResolver;
use TZachi\PhalconRepository\Resolver\QueryParameter as QueryParameterResolver;
use TZachi\PhalconRepository\Tests\Mock\Model\Company;
use TZachi\PhalconRepository\Tests\Mock\Model\CompanyAnnotationAbsent;
use TZachi\PhalconRepository\Tests\Mock\Model\CompanyInvalid;
use TZachi\PhalconRepository\Tests\Mock\Model\CompanyMissing;
use TZachi\PhalconRepository\Tests\Mock\Model\CompanyRepositoryAnnotationAbsent;
use TZachi\PhalconRepository\Tests\Mock\Repository\Company as CompanyRepository;
use function get_class;

final class RepositoryFactoryTest extends TestCase
{
    /**
     * @var MemoryAdapter
     */
    private $annotations;

    /**
     * @var ParameterResolver
     */
    private $parameterResolver;

    /**
     * @var RepositoryFactory
     */
    private $factory;

    /**
     * @before
     */
    public function createDependencies(): void
    {
        $this->annotations       = new MemoryAdapter();
        $this->parameterResolver = new QueryParameterResolver();
        $this->factory           = new RepositoryFactory($this->annotations, $this->parameterResolver);
    }

    /**
     * @test
     */
    public function createShouldUseRepositoryInAnnotations(): void
    {
        self::assertInstanceOf(CompanyRepository::class, $this->factory->create(Company::class));
    }

    /**
     * @test
     */
    public function createShouldUseDefaultRepositoryWhenThereAreNoAnnotations(): void
    {
        // Make sure that the result repository is not an instance of a Repository subclass, but the actual class
        self::assertSame(Repository::class, get_class($this->factory->create(CompanyAnnotationAbsent::class)));
    }

    /**
     * @test
     */
    public function createShouldUseDefaultRepositoryWhenRepositoryAnnotationIsNotSpecified(): void
    {
        // Make sure that the result repository is not an instance of a Repository subclass, but the actual class
        self::assertSame(
            Repository::class,
            get_class($this->factory->create(CompanyRepositoryAnnotationAbsent::class))
        );
    }

    /**
     * @test
     * @dataProvider createInvalidData
     */
    public function createShouldThrowExceptionWhenRepositoryAnnotationIsInvalid(string $className): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp("/Repository class '[^']*' doesn't exists/i");

        $this->factory->create($className);
    }

    /**
     * @return string[][]
     */
    public function createInvalidData(): array
    {
        return [
            'Invalid class' => ['className' => CompanyInvalid::class],
            'Missing argument' => ['className' => CompanyMissing::class],
        ];
    }
}
