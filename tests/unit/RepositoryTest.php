<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Test\Unit;

use InvalidArgumentException;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\Model\ResultsetInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TZachi\PhalconRepository\ModelWrapper;
use TZachi\PhalconRepository\Repository;

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
     * @var Repository
     */
    private $repository;

    /**
     * @before
     */
    public function setUpDependencies(): void
    {
        $this->modelWrapper = $this->createPartialMock(ModelWrapper::class, ['find', 'findFirst', 'query']);

        $this->repository = new Repository($this->modelWrapper);
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     * @depends orderByToParametersShouldConvertValidArgs
     */
    public function findFirstWhereShouldGetValidParametersAndReturnModel(): void
    {
        $where    = [
            'field' => 1,
            'field2' => 'abc',
        ];
        $orderBy  = ['field', 'field2' => 'DESC'];
        $expected = [
            'conditions' => '[field] = ?0 AND [field2] = ?1',
            'bind' => [1, 'abc'],
            'order' => '[field] ASC, [field2] DESC',
        ];

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
     * @depends whereToParametersShouldConvertValidArgs
     * @depends orderByToParametersShouldConvertValidArgs
     */
    public function findFirstByShouldReturnModel(): void
    {
        /**
         * @var Model|MockObject $model
         */
        $model = $this->createMock(Model::class);
        $this->modelWrapper->expects(self::once())
            ->method('findFirst')
            ->willReturn($model);

        self::assertSame($model, $this->repository->findFirstBy('field', 1));
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     * @depends orderByToParametersShouldConvertValidArgs
     */
    public function findFirstByShouldReturnNullWhenWrapperReturnsFalse(): void
    {
        $this->modelWrapper->expects(self::once())
            ->method('findFirst')
            ->willReturn(false);

        self::assertNull($this->repository->findFirstBy('field', 1));
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     */
    public function findFirstShouldReturnModel(): void
    {
        /**
         * @var Model|MockObject $model
         */
        $model = $this->createMock(Model::class);
        $this->modelWrapper->expects(self::once())
            ->method('findFirst')
            ->willReturn($model);

        self::assertSame($model, $this->repository->findFirst(1));
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     */
    public function findFirstShouldUseCustomId(): void
    {
        $repository = new Repository($this->modelWrapper, 'customIdField');

        /**
         * @var Model|MockObject $model
         */
        $model = $this->createMock(Model::class);

        $this->modelWrapper->expects(self::once())
            ->method('findFirst')
            ->with(self::identicalTo([
                'conditions' => '[customIdField] = ?0',
                'bind' => [1],
            ]))
            ->willReturn($model);

        self::assertSame($model, $repository->findFirst(1));
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     */
    public function findFirstShouldReturnNullWhenWrapperReturnsFalse(): void
    {
        $this->modelWrapper->expects(self::once())
            ->method('findFirst')
            ->willReturn(false);

        self::assertNull($this->repository->findFirst(1));
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     * @depends orderByToParametersShouldConvertValidArgs
     * @dataProvider findByValidData
     *
     * @param mixed[]  $expectedParams
     * @param string[] $orderBy
     */
    public function findByShouldReturnResultsetWithValidData(
        string $fieldName,
        string $fieldValue,
        array $expectedParams,
        array $orderBy,
        int $limit = 0,
        int $offset = 0
    ): void {
        /**
         * @var ResultsetInterface|MockObject $model
         */
        $resultSet = $this->createMock(ResultsetInterface::class);

        $this->modelWrapper->expects(self::once())
            ->method('find')
            ->with(self::identicalTo($expectedParams))
            ->willReturn($resultSet);

        self::assertSame(
            $resultSet,
            $this->repository->findBy($fieldName, $fieldValue, $orderBy, $limit, $offset)
        );
    }

    /**
     * @return mixed[]
     */
    public function findByValidData(): array
    {
        $testData = 'test value';

        return [
            'Params only' => [
                'fieldName' => 'field',
                'fieldValue' => $testData,
                'expectedParams' => [
                    'conditions' => '[field] = ?0',
                    'bind' => [0 => $testData],
                ],
                'orderBy' => [],
                'limit' => 0,
                'offset' => 0,
            ],
            'With order by' => [
                'fieldName' => 'field',
                'fieldValue' => $testData,
                'expectedParams' => [
                    'conditions' => '[field] = ?0',
                    'bind' => [0 => $testData],
                    'order' => '[field] ASC, [field2] DESC',
                ],
                'orderBy' => [
                    'field' => 'ASC',
                    'field2' => 'DESC',
                ],
                'limit' => 0,
                'offset' => 0,
            ],
            'With order by 2' => [
                'fieldName' => 'field',
                'fieldValue' => $testData,
                'expectedParams' => [
                    'conditions' => '[field] = ?0',
                    'bind' => [0 => $testData],
                    'order' => '[field] ASC, [field2] ASC',
                ],
                'orderBy' => ['field', 'field2'],
                'limit' => 0,
                'offset' => 0,
            ],
            'With limit' => [
                'fieldName' => 'field',
                'fieldValue' => $testData,
                'expectedParams' => [
                    'conditions' => '[field] = ?0',
                    'bind' => [0 => $testData],
                    'order' => '[field] ASC',
                    'offset' => 0,
                    'limit' => 10,
                ],
                'orderBy' => ['field'],
                'limit' => 10,
                'offset' => 0,
            ],
            'With offset and limit' => [
                'fieldName' => 'field',
                'fieldValue' => $testData,
                'expectedParams' => [
                    'conditions' => '[field] = ?0',
                    'bind' => [0 => $testData],
                    'order' => '[field] ASC, [field2] ASC',
                    'offset' => 10,
                    'limit' => 5,
                ],
                'orderBy' => ['field', 'field2'],
                'limit' => 5,
                'offset' => 10,
            ],
        ];
    }

    /**
     * @test
     */
    public function queryShouldReturnCriteria(): void
    {
        /**
         * @var DiInterface|MockObject $di
         */
        $di = $this->createMock(DiInterface::class);
        /**
         * @var Criteria|MockObject $di
         */
        $criteria = $this->createMock(Criteria::class);

        $this->modelWrapper->expects(self::once())
            ->method('query')
            ->with(self::identicalTo($di))
            ->willReturn($criteria);

        static::assertSame($criteria, $this->repository->query($di));
    }

    /**
     * @test
     * @dataProvider whereParamValidData
     *
     * @param mixed[] $expected
     * @param mixed[] $where
     */
    public function whereToParametersShouldConvertValidArgs(array $expected, array $where): void
    {
        self::assertSame($expected, $this->repository->whereToParameters($where));
    }

    /**
     * @return mixed[]
     */
    public function whereParamValidData(): array
    {
        return [
            'Empty where' => [
                'expected' => [],
                'where' => [],
            ],
            'Simple where' => [
                'expected' => [
                    'conditions' => '[test] IS NULL AND [test2] IN (?0, ?1, ?2) AND [test3] = ?3',
                    'bind' => [
                        0 => 'zero',
                        1 => 'one',
                        2 => 'two',
                        3 => 'three',
                    ],
                ],
                'where' => [
                    'test' => null,
                    'test2' => ['zero', 'one', 'two'],
                    'test3' => 'three',
                ],
            ],
            'Simple where with different operators' => [
                'expected' => [
                    'conditions' => '[test] IS NULL AND ([numericField] > ?0) AND ([numericField] <= ?1) '
                        . 'AND ([dateField] BETWEEN ?2 AND ?3)',
                    'bind' => [
                        0 => 50,
                        1 => 150,
                        2 => '2019-01-01',
                        3 => '2019-01-31',
                    ],
                ],
                'where' => [
                    'test' => null,
                    [
                        '@operator' => '>',
                        'numericField' => 50,
                    ],
                    [
                        '@operator' => '<=',
                        'numericField' => 150,
                    ],
                    [
                        '@operator' => 'BETWEEN',
                        'dateField' => ['2019-01-01', '2019-01-31'],
                    ],
                ],
            ],
            'Composite where' => [
                'expected' => [
                    'conditions' => '[test] IN (?0, ?1, ?2) '
                        . 'AND ([test3] = ?3 OR [test4] = ?4 OR ([test5] = ?5 AND [test6] = ?6)) '
                        . 'AND ([test7] = ?7 OR [test8] = ?8) AND ([numericField] BETWEEN ?9 AND ?10)',
                    'bind' => [
                        0 => 'zero',
                        1 => 'one',
                        2 => 'two',
                        3 => 'three',
                        4 => 'four',
                        5 => 'five',
                        6 => 'six',
                        7 => 'seven',
                        8 => 'eight',
                        9 => 1,
                        10 => 10,
                    ],
                ],
                'where' => [
                    'test' => ['zero', 'one', 'two'],
                    [
                        '@type' => Repository::TYPE_OR,
                        'test3' => 'three',
                        'test4' => 'four',
                        [
                            '@type' => Repository::TYPE_AND,
                            'test5' => 'five',
                            'test6' => 'six',
                        ],
                    ],
                    [
                        '@type' => Repository::TYPE_OR,
                        'test7' => 'seven',
                        'test8' => 'eight',
                    ],
                    [
                        '@operator' => 'BETWEEN',
                        'numericField' => [1, 10],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     */
    public function whereToParametersShouldThrowExceptionWithInvalidBetweenValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/must be an array with exactly two values/i');

        $this->repository->whereToParameters([
            '@operator' => 'BETWEEN',
            'numericField' => [1],
        ]);
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     */
    public function whereToParametersShouldThrowExceptionWithEmptyArrayValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/empty array value is not allowed/i');

        $this->repository->whereToParameters([
            'multipleFields' => [],
        ]);
    }

    /**
     * @test
     * @depends whereToParametersShouldConvertValidArgs
     */
    public function whereToParametersShouldThrowExceptionWithInvalidOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/\* is not a valid operator/i');

        $this->repository->whereToParameters([
            '@operator' => '*',
            'field1' => 'value1',
        ]);
    }

    /**
     * @test
     * @dataProvider orderByParamValidData
     *
     * @param mixed[]  $expected
     * @param string[] $orderBy
     */
    public function orderByToParametersShouldConvertValidArgs(array $expected, array $orderBy): void
    {
        self::assertSame($expected, $this->repository->orderByToParameters($orderBy));
    }

    /**
     * @return mixed[]
     */
    public function orderByParamValidData(): array
    {
        return [
            'Empty orderBy' => [
                'expected' => [],
                'orderBy' => [],
            ],
            'Sort direction specified for every field' => [
                'expected' => ['order' => '[field1] ASC, [field2] DESC, [field3] ASC'],
                'orderBy' => ['field1' => 'ASC', 'field2' => 'desc', 'field3' => 'asc'],
            ],
            'Sort direction not specified' => [
                'expected' => ['order' => '[field1] ASC, [field2] ASC, [field3] ASC'],
                'orderBy' => ['field1', 'field2', 'field3'],
            ],
            'Mixed' => [
                'expected' => ['order' => '[field1] ASC, [field2] DESC, [field3] ASC, [field4] DESC, [field4] ASC'],
                'orderBy' => [
                    'field1' => 'ASC',
                    'field2' => 'DESC',
                    0 => 'field3',
                    'field4' => 'DESC',
                    1 => 'field4',
                ],
            ],
        ];
    }
}
