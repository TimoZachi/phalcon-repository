<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Unit;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultset;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TZachi\PhalconRepository\ModelWrapper;
use TZachi\PhalconRepository\Repository;
use TZachi\PhalconRepository\Resolver\QueryParameter;

/**
 * @coversDefaultClass Repository
 */
final class RepositoryTest extends TestCase
{
    /**
     * @var ModelWrapper|MockObject
     */
    private $modelWrapper;

    /**
     * @var QueryParameter|MockObject
     */
    private $queryParameterResolver;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @before
     */
    public function setUpDependencies(): void
    {
        $this->modelWrapper           = $this->createMock(ModelWrapper::class);
        $this->queryParameterResolver = $this->createMock(QueryParameter::class);

        $this->repository = new Repository($this->modelWrapper, $this->queryParameterResolver);
    }

    /**
     * @test
     */
    public function findFirstWhereShouldCombineParametersAndReturnModel(): void
    {
        $where    = ['field' => 'x'];
        $orderBy  = ['a'];
        $expected = [
            'conditions' => '[field] = ?0',
            'bind' => ['x'],
            'order' => '[field] ASC',
        ];

        $this->queryParameterResolver->expects(self::once())
            ->method('where')
            ->with(self::identicalTo($where))
            ->willReturn(['conditions' => '[field] = ?0', 'bind' => ['x']]);

        $this->queryParameterResolver->expects(self::once())
            ->method('orderBy')
            ->with(self::identicalTo($orderBy))
            ->willReturn(['order' => '[field] ASC']);

        /**
         * @var Model|MockObject $model
         */
        $model = $this->createMock(Model::class);
        $this->modelWrapper->expects(self::once())
            ->method('findFirst')
            ->with(self::identicalTo($expected))
            ->willReturn($model);

        self::assertSame($model, $this->repository->findFirstWhere($where, $orderBy));
    }

    /**
     * @test
     */
    public function findFirstWhereShouldReturnNullWhenModelWrapperReturnsFalse(): void
    {
        $this->modelWrapper->expects(self::once())
            ->method('findFirst')
            ->willReturn(false);

        self::assertNull($this->repository->findFirstWhere());
    }

    /**
     * @test
     */
    public function findFirstByShouldCallFindFirstWhereAndReturnModel(): void
    {
        /**
         * @var Repository|MockObject $repository
         */
        $repository = $this->createPartialMock(Repository::class, ['findFirstWhere']);

        /**
         * @var Model|MockObject $model
         */
        $model = $this->createMock(Model::class);
        $repository->expects(self::once())
            ->method('findFirstWhere')
            ->with(self::identicalTo(['field' => 1]), self::identicalTo(['field' => 'DESC']))
            ->willReturn($model);

        self::assertSame($model, $repository->findFirstBy('field', 1, ['field' => 'DESC']));
    }

    /**
     * @test
     */
    public function findFirstByShouldReturnNullWhenFindFirstWhereReturnsNull(): void
    {
        /**
         * @var Repository|MockObject $repository
         */
        $repository = $this->createPartialMock(Repository::class, ['findFirstWhere']);

        $repository->expects(self::once())
            ->method('findFirstWhere')
            ->willReturn(null);

        self::assertNull($repository->findFirstBy('field', 1));
    }

    /**
     * @test
     */
    public function findFirstShouldCallFindFirstByWithDefaultIdAndReturnModel(): void
    {
        $repository = $this->createRepositoryMock(['findFirstBy']);

        /**
         * @var Model|MockObject $model
         */
        $model = $this->createMock(Model::class);
        $repository->expects(self::once())
            ->method('findFirstBy')
            ->with(self::identicalTo('id'), self::identicalTo(1))
            ->willReturn($model);

        self::assertSame($model, $repository->findFirst(1));
    }

    /**
     * @test
     */
    public function findFirstShouldUseCustomId(): void
    {
        $repository = $this->createRepositoryMock(['findFirstBy'], 'customId');

        /**
         * @var Model|MockObject $model
         */
        $model = $this->createMock(Model::class);
        $repository->expects(self::once())
            ->method('findFirstBy')
            ->with(self::identicalTo('customId'), self::identicalTo(1))
            ->willReturn($model);

        $repository->findFirst(1);
    }

    /**
     * @test
     */
    public function findFirstShouldReturnNullWhenFindFirstByReturnsFalse(): void
    {
        $repository = $this->createRepositoryMock(['findFirstBy']);

        $repository->expects(self::once())
            ->method('findFirstBy')
            ->willReturn(null);

        self::assertNull($repository->findFirst(1));
    }

    /**
     * @test
     */
    public function findWhereShouldCombineParametersAndReturnSimpleResultset(): void
    {
        $where    = ['field' => 'x'];
        $orderBy  = ['a'];
        $limit    = 10;
        $offset   = 5;
        $expected = [
            'conditions' => '[field] = ?0',
            'bind' => ['x'],
            'order' => '[field] ASC',
            'limit' => 10,
            'offset' => 5,
        ];

        $this->queryParameterResolver->expects(self::once())
            ->method('where')
            ->with(self::identicalTo($where))
            ->willReturn(['conditions' => '[field] = ?0', 'bind' => ['x']]);

        $this->queryParameterResolver->expects(self::once())
            ->method('orderBy')
            ->with(self::identicalTo($orderBy))
            ->willReturn(['order' => '[field] ASC']);

        $this->queryParameterResolver->expects(self::once())
            ->method('limit')
            ->with(self::identicalTo(10), self::identicalTo(5))
            ->willReturn(['limit' => 10, 'offset' => 5]);

        /**
         * @var SimpleResultset|MockObject $resultSet
         */
        $resultSet = $this->createMock(SimpleResultset::class);
        $this->modelWrapper->expects(self::once())
            ->method('find')
            ->with(self::identicalTo($expected))
            ->willReturn($resultSet);

        self::assertSame($resultSet, $this->repository->findWhere($where, $orderBy, $limit, $offset));
    }

    /**
     * @test
     */
    public function findByShouldCallFindWhereAndReturnSimpleResultset(): void
    {
        $repository = $this->createRepositoryMock(['findWhere']);

        $orderBy = ['a'];
        $limit   = 10;
        $offset  = 5;

        /**
         * @var SimpleResultset|MockObject $resultSet
         */
        $resultSet = $this->createMock(SimpleResultset::class);
        $repository->expects(self::once())
            ->method('findWhere')
            ->with(
                self::identicalTo(['field' => 'x']),
                self::identicalTo($orderBy),
                self::identicalTo($limit),
                self::identicalTo($offset)
            )
            ->willReturn($resultSet);

        $repository->findBy('field', 'x', $orderBy, $limit, $offset);
    }

    /**
     * @test
     */
    public function queryShouldReturnCriteria(): void
    {
        /**
         * @var Criteria|MockObject $di
         */
        $criteria = $this->createMock(Criteria::class);

        $this->modelWrapper->expects(self::once())
            ->method('query')
            ->willReturn($criteria);

        static::assertSame($criteria, $this->repository->query());
    }

    /**
     * @test
     */
    public function countShouldCombineParametersWhenColumnIsSpecified(): void
    {
        [$column, $where] = $this->createScenarioForAggregationMethodTest('count', 102);

        self::assertSame(102, $this->repository->count($column, $where));
    }

    /**
     * @test
     */
    public function countShouldNotGetColumnWhenColumnParameterIsNull(): void
    {
        $expected = [
            'conditions' => '[field] = ?0',
            'bind' => [1],
        ];

        $this->queryParameterResolver->expects(self::once())
            ->method('where')
            ->with(['field' => 1])
            ->willReturn($expected);

        $this->modelWrapper->expects(self::once())
            ->method('count')
            ->with(self::identicalTo($expected))
            ->willReturn(10);

        self::assertSame(10, $this->repository->count(null, ['field' => 1]));
    }

    /**
     * @test
     */
    public function sumShouldCombineParametersAndReturnFloat(): void
    {
        [$column, $where] = $this->createScenarioForAggregationMethodTest('sum', '101.3');

        self::assertSame(101.3, $this->repository->sum($column, $where));
    }

    /**
     * @test
     */
    public function sumShouldReturnNullWhenModelWrapperReturnsNull(): void
    {
        [$column, $where] = $this->createScenarioForAggregationMethodTest('sum', null);

        self::assertNull($this->repository->sum($column, $where));
    }

    /**
     * @test
     */
    public function averageShouldCombineParametersAndReturnFloat(): void
    {
        [$column, $where] = $this->createScenarioForAggregationMethodTest('average', '10.5');

        self::assertSame(10.5, $this->repository->average($column, $where));
    }

    /**
     * @test
     */
    public function averageShouldReturnNullWhenModelWrapperReturnsNull(): void
    {
        [$column, $where] = $this->createScenarioForAggregationMethodTest('average', null);

        self::assertNull($this->repository->average($column, $where));
    }

    /**
     * @test
     */
    public function minimumShouldCombineParametersAndReturnMinimum(): void
    {
        $returnValue      = '2019-01-01';
        [$column, $where] = $this->createScenarioForAggregationMethodTest('minimum', $returnValue);

        self::assertSame($returnValue, $this->repository->minimum($column, $where));
    }

    /**
     * @test
     */
    public function minimumShouldReturnNullWhenModelWrapperReturnsNull(): void
    {
        [$column, $where] = $this->createScenarioForAggregationMethodTest('minimum', null);

        self::assertNull($this->repository->minimum($column, $where));
    }

    /**
     * @test
     */
    public function maximumShouldCombineParametersAndReturnMaximum(): void
    {
        $returnValue      = '2019-01-31';
        [$column, $where] = $this->createScenarioForAggregationMethodTest('maximum', $returnValue);

        self::assertSame($returnValue, $this->repository->maximum($column, $where));
    }

    /**
     * @test
     */
    public function maximumShouldReturnNullWhenModelWrapperReturnsNull(): void
    {
        [$column, $where] = $this->createScenarioForAggregationMethodTest('maximum', null);

        self::assertNull($this->repository->maximum($column, $where));
    }

    /**
     * @param string[] $methods
     *
     * @return Repository|MockObject
     */
    private function createRepositoryMock(array $methods, ?string $customId = null)
    {
        $constructorArgs = [
            $this->modelWrapper,
            $this->queryParameterResolver,
        ];
        if ($customId !== null) {
            $constructorArgs[] = $customId;
        }

        return $this->getMockBuilder(Repository::class)
            ->setConstructorArgs($constructorArgs)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param string|int|null $returnValue
     *
     * @return mixed[]
     */
    private function createScenarioForAggregationMethodTest(string $methodName, $returnValue): array
    {
        $column   = 'testColumn';
        $where    = ['field' => 1];
        $expected = [
            'column' => 'testColumn',
            'conditions' => '[field] = ?0',
            'bind' => [1],
        ];

        $this->queryParameterResolver->expects(self::once())
            ->method('where')
            ->with(self::identicalTo($where))
            ->willReturn(['conditions' => '[field] = ?0', 'bind' => [1]]);
        $this->queryParameterResolver->expects(self::once())
            ->method('column')
            ->with(self::identicalTo($column))
            ->willReturn(['column' => $column]);

        $this->modelWrapper->expects(self::once())
            ->method($methodName)
            ->with(self::identicalTo($expected))
            ->willReturn($returnValue);

        return [$column, $where];
    }
}
