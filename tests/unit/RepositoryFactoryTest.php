<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Unit;

use Phalcon\Annotations\AdapterInterface as AnnotationsAdapterInterface;
use Phalcon\Annotations\Annotation;
use Phalcon\Annotations\Collection;
use Phalcon\Annotations\Reflection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TZachi\PhalconRepository\Repository;
use TZachi\PhalconRepository\RepositoryFactory;
use TZachi\PhalconRepository\Resolver\Parameter as ParameterResolver;
use TZachi\PhalconRepository\Tests\Mock\Repository\Company as CompanyRepository;
use function get_class;

final class RepositoryFactoryTest extends TestCase
{
    /**
     * @var Annotation|MockObject
     */
    private $annotation;

    /**
     * @var Collection|MockObject
     */
    private $collection;


    /**
     * @var Reflection|MockObject
     */
    private $reflection;

    /**
     * @var AnnotationsAdapterInterface|MockObject
     */
    private $annotations;

    /**
     * @var ParameterResolver|MockObject
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
        $this->annotation        = $this->createMock(Annotation::class);
        $this->collection        = $this->createMock(Collection::class);
        $this->reflection        = $this->createMock(Reflection::class);
        $this->annotations       = $this->createMock(AnnotationsAdapterInterface::class);
        $this->parameterResolver = $this->createMock(ParameterResolver::class);
        $this->factory           = new RepositoryFactory($this->annotations, $this->parameterResolver);
    }

    /**
     * @test
     */
    public function getShouldUseSameRepositoryInstanceOnMultipleCalls(): void
    {
        $repository = $this->createMock(Repository::class);

        /**
         * @var RepositoryFactory|MockObject $factory
         */
        $factory = $this->createPartialMock(RepositoryFactory::class, ['create']);
        $factory->expects(self::once())
            ->method('create')
            ->with('Model')
            ->willReturn($repository);

        // Multiple calls to `get` to make sure that `create` will only be called once
        self::assertSame($repository, $factory->get('Model'));
        self::assertSame($repository, $factory->get('Model'));
        self::assertSame($repository, $factory->get('Model'));
    }

    /**
     * @test
     */
    public function createShouldUseRepositoryInAnnotations(): void
    {
        $this->createScenarioForTest(true, true, CompanyRepository::class);

        self::assertInstanceOf(CompanyRepository::class, $this->factory->create('Model'));
    }

    /**
     * @test
     */
    public function createShouldUseDefaultRepositoryWhenThereAreNoAnnotations(): void
    {
        $this->createScenarioForTest(false, false, null);

        // Make sure that the result repository is not an instance of a Repository subclass, but the actual class
        self::assertSame(Repository::class, get_class($this->factory->create('Model')));
    }

    /**
     * @test
     * @depends createShouldUseDefaultRepositoryWhenThereAreNoAnnotations
     */
    public function createShouldReceiveCorrectUseSpecifiedParameterResolver(): void
    {
        $this->createScenarioForTest(false, false, null);

        $repository = $this->factory->create('Model');
        self::assertSame($this->parameterResolver, $repository->getParameterResolver());
    }

    /**
     * @test
     */
    public function createShouldUseDefaultRepositoryWhenRepositoryAnnotationIsNotSpecified(): void
    {
        $this->createScenarioForTest(true, false, null);

        // Make sure that the result repository is not an instance of a Repository subclass, but the actual class
        self::assertSame(Repository::class, get_class($this->factory->create('Model')));
    }

    /**
     * @test
     */
    public function createShouldThrowExceptionWithNoAnnotationParameter(): void
    {
        $this->createScenarioForTest(true, true, null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Repository class '' doesn't exists");

        $this->factory->create('Model');
    }

    /**
     * @test
     */
    public function createShouldThrowExceptionWhenRepositoryAnnotationIsInvalid(): void
    {
        $this->createScenarioForTest(true, true, 'Inexistent\Class\Name');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Repository class 'Inexistent\Class\Name' doesn't exists");

        $this->factory->create('Model');
    }

    private function createScenarioForTest(
        bool $hasAnnotations,
        bool $hasRepositoryAnnotation,
        ?string $annotationArgument
    ): void {
        $this->annotations->expects(self::once())
            ->method('get')
            ->with(self::identicalTo('Model'))
            ->willReturn($this->reflection);

        $this->reflection->expects(self::once())
            ->method('getClassAnnotations')
            ->willReturn($hasAnnotations ? $this->collection : false);

        if (!$hasAnnotations) {
            return;
        }

        $this->collection->expects(self::once())
            ->method('has')
            ->with(self::identicalTo(RepositoryFactory::REPOSITORY_ANNOTATION_NAME))
            ->willReturn($hasRepositoryAnnotation);

        if (!$hasRepositoryAnnotation) {
            return;
        }

        $this->annotation->expects(self::once())
            ->method('getArgument')
            ->with(self::identicalTo(0))
            ->willReturn($annotationArgument);

        $this->collection->expects(self::once())
            ->method('get')
            ->with(self::identicalTo(RepositoryFactory::REPOSITORY_ANNOTATION_NAME))
            ->willReturn($this->annotation);
    }
}
