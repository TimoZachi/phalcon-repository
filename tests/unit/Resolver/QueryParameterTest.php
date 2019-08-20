<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Unit\Resolver;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TZachi\PhalconRepository\Repository;
use TZachi\PhalconRepository\Resolver\Parameter;
use TZachi\PhalconRepository\Resolver\QueryParameter;

/**
 * @coversDefaultClass Repository
 */
class QueryParameterTest extends TestCase
{
    /**
     * @var QueryParameter
     */
    private $queryParameter;

    public function setUp(): void
    {
        $this->queryParameter = new QueryParameter();
    }

    /**
     * @test
     * @dataProvider provideValidWhereParameters
     *
     * @param mixed[] $expected
     * @param mixed[] $where
     */
    public function whereShouldResolveToValidParameters(array $expected, array $where): void
    {
        self::assertSame($expected, $this->queryParameter->where($where));
    }

    /**
     * @return mixed[]
     */
    public function provideValidWhereParameters(): array
    {
        return [
            'Empty where' => [[], []],
            'Simple where' => [
                [
                    'conditions' => '[test] IS NULL AND [test2] IN (?0, ?1, ?2) AND [test3] = ?3',
                    'bind' => ['zero', 'one', 'two', 'three'],
                ],
                [
                    'test' => null,
                    'test2' => ['zero', 'one', 'two'],
                    'test3' => 'three',
                ],
            ],
            'Simple where with different operators' => [
                [
                    'conditions' => '[test] IS NULL AND ([dateField] BETWEEN ?0 AND ?1) AND ' .
                        '([numericField] > ?2) AND ([numericField] <= ?3) AND ([stringField] LIKE ?4)',
                    'bind' => ['2019-01-01', '2019-01-31', 50, 150, 'Timo%'],
                ],
                [
                    'test' => null,
                    [
                        '@operator' => 'BETWEEN',
                        'dateField' => ['2019-01-01', '2019-01-31'],
                    ],
                    [
                        '@operator' => '>',
                        'numericField' => 50,
                    ],
                    [
                        '@operator' => '<=',
                        'numericField' => 150,
                    ],
                    [
                        '@operator' => 'LIKE',
                        'stringField' => 'Timo%',
                    ],
                ],
            ],
            'Composite where' => [
                [
                    'conditions' => '[test] IN (?0, ?1, ?2) '
                        . 'AND ([test3] = ?3 OR [test4] = ?4 OR ([test5] = ?5 AND [test6] = ?6)) '
                        . 'AND ([test7] = ?7 OR [test8] = ?8) AND ([numericField] BETWEEN ?9 AND ?10) '
                        . 'AND ([numericField2] NOT IN (?11, ?12))',
                    'bind' => ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 1, 10, 9, 20],
                ],
                [
                    'test' => ['zero', 'one', 'two'],
                    [
                        '@type' => Parameter::TYPE_OR,
                        'test3' => 'three',
                        'test4' => 'four',
                        [
                            '@type' => Parameter::TYPE_AND,
                            'test5' => 'five',
                            'test6' => 'six',
                        ],
                    ],
                    [
                        '@type' => Parameter::TYPE_OR,
                        'test7' => 'seven',
                        'test8' => 'eight',
                    ],
                    [
                        '@operator' => 'BETWEEN',
                        'numericField' => [1, 10],
                    ],
                    [
                        '@operator' => '<>',
                        'numericField2' => [9, 20],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @depends whereShouldResolveToValidParameters
     * @dataProvider provideOperatorsThatNeedAnArrayValue
     */
    public function whereShouldThrowExceptionWhenOperatorValueShouldBeAnArrayAndItIsNot(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/operator [^ ]+ needs an array as its value/i');

        $this->queryParameter->where([
            '@operator' => $operator,
            'field1' => 'non array value',
        ]);
    }

    /**
     * @return mixed[]
     */
    public function provideOperatorsThatNeedAnArrayValue(): array
    {
        // At the moment, only the between operator must have an array as its value
        return [
            ['BETWEEN'],
        ];
    }

    /**
     * @test
     * @depends whereShouldResolveToValidParameters
     * @dataProvider provideOperatorsThatCannotHaveAnArrayValue
     */
    public function whereShouldThrowAnExceptionWhenOperatorValueShouldNotBeAnArrayAndItIs(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/operator [^ ]+ cannot have an array as its value/i');

        $this->queryParameter->where([
            '@operator' => $operator,
            'field' => [1, 2, 3],
        ]);
    }

    /**
     * @return mixed[]
     */
    public function provideOperatorsThatCannotHaveAnArrayValue(): array
    {
        return [
            ['<='],
            ['>='],
            ['<'],
            ['>'],
            ['LIKE'],
        ];
    }

    /**
     * @test
     * @depends whereShouldResolveToValidParameters
     * @dataProvider provideOperatorsThatCanHaveArrayAndScalarValues
     */
    public function whereShouldAllowArrayAndScalarForCertainOperators(string $operator): void
    {
        $this->expectNotToPerformAssertions();

        $this->queryParameter->where([
            '@operator' => $operator,
            'field' => [1, 2, 3],
        ]);
        $this->queryParameter->where([
            '@operator' => $operator,
            'field' => 'value',
        ]);
    }

    /**
     * @return mixed[]
     */
    public function provideOperatorsThatCanHaveArrayAndScalarValues(): array
    {
        return [
            ['='],
            ['<>'],
        ];
    }

    /**
     * @test
     * @depends whereShouldResolveToValidParameters
     */
    public function whereShouldThrowExceptionWhenBetweenValueIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/must be an array with exactly two values/i');

        $this->queryParameter->where([
            '@operator' => 'BETWEEN',
            'numericField' => [1],
        ]);
    }

    /**
     * @test
     * @depends whereShouldResolveToValidParameters
     */
    public function whereShouldThrowExceptionWhenArrayValueIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/empty array value is not allowed/i');

        $this->queryParameter->where(['id' => []]);
    }

    /**
     * @test
     * @depends whereShouldResolveToValidParameters
     */
    public function whereShouldThrowExceptionWhenConfigOperatorIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/configuration @operator must be one of/i');

        $this->queryParameter->where([
            '@operator' => '*',
            'field1' => 'value1',
        ]);
    }

    /**
     * @test
     * @depends whereShouldResolveToValidParameters
     */
    public function whereShouldThrowExceptionWhenConfigTypeIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/configuration @type must be one of/i');

        $this->queryParameter->where([
            '@type' => 'INVALID TYPE',
            'field1' => 'value1',
        ]);
    }

    /**
     * @test
     * @dataProvider provideValidOrderByParameters
     *
     * @param mixed[]  $expected
     * @param string[] $orderBy
     */
    public function orderByShouldResolveValidParameters(array $expected, array $orderBy): void
    {
        self::assertSame($expected, $this->queryParameter->orderBy($orderBy));
    }

    /**
     * @return mixed[]
     */
    public function provideValidOrderByParameters(): array
    {
        return [
            'Empty orderBy' => [[], []],
            'Sort direction specified for every field' => [
                ['order' => '[field1] ASC, [field2] DESC, [field3] ASC'],
                ['field1' => 'ASC', 'field2' => 'desc', 'field3' => 'asc'],
            ],
            'Sort direction not specified' => [
                ['order' => '[field1] ASC, [field2] ASC, [field3] ASC'],
                ['field1', 'field2', 'field3'],
            ],
            'Mixed' => [
                ['order' => '[field1] ASC, [field2] DESC, [field3] ASC, [field4] DESC, [field4] ASC'],
                [
                    'field1' => 'ASC',
                    'field2' => 'DESC',
                    0 => 'field3',
                    'field4' => 'DESC',
                    1 => 'field4',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function orderByShouldThrowExceptionWhenSortDirectionIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Sort direction must be one of/i');

        $this->queryParameter->orderBy(['field' => 'INVALID SORT DIRECTION']);
    }

    /**
     * @test
     */
    public function limitShouldResolveToEmptyArrayWhenZeroIsSpecifiedAsLimit(): void
    {
        static::assertSame([], $this->queryParameter->limit(0));
    }

    /**
     * @test
     */
    public function limitShouldDefaultOffsetToZero(): void
    {
        static::assertSame(['limit' => 10, 'offset' => 0], $this->queryParameter->limit(10));
    }

    /**
     * @test
     */
    public function limitShouldResolveWLimitAndOffset(): void
    {
        static::assertSame(['limit' => 10, 'offset' => 30], $this->queryParameter->limit(10, 30));
    }

    /**
     * @test
     */
    public function columnShouldResolveValidParameters(): void
    {
        static::assertSame(['column' => 'testColumn'], $this->queryParameter->column('testColumn'));
    }
}
