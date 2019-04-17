<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Unit;

use Phalcon\DiInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultset;
use PHPUnit\Framework\TestCase;
use TZachi\PhalconRepository\ModelWrapper;
use TZachi\PhalconRepository\Tests\Mock\Model\Wrapper;

class ModelWrapperTest extends TestCase
{
    /**
     * @test
     */
    public function constructorShouldAssignModelName(): void
    {
        $modelClass = Wrapper::class;

        $modelWrapper = new ModelWrapper($modelClass);

        self::assertSame($modelClass, $modelWrapper->getModelName());
    }

    /**
     * @test
     * @dataProvider methodNamesData
     *
     * @param mixed[] $args
     * @param mixed   $returnValue
     */
    public function allMethodsInWrapperShouldReturnSameAsModel(string $methodName, array $args, $returnValue): void
    {
        Wrapper::$methodName  = $methodName;
        Wrapper::$args        = $args;
        Wrapper::$returnValue = $returnValue;

        $modelWrapper = new ModelWrapper(Wrapper::class);

        static::assertSame($returnValue, $modelWrapper->{$methodName}(...$args));
    }

    /**
     * @return mixed[]
     */
    public function methodNamesData(): array
    {
        $defaultArgs = [
            [
                'conditions' => 'field1 => :value:',
                'binds' => ['value' => 1],
            ],
        ];

        return [
            [
                'methodName' => 'findFirst',
                'args' => $defaultArgs,
                'returnData' => $this->createMock(Model::class),
            ],
            [
                'methodName' => 'findFirst',
                'args' => $defaultArgs,
                'returnData' => false,
            ],
            [
                'methodName' => 'find',
                'args' => $defaultArgs,
                'returnData' => $this->createMock(SimpleResultset::class),
            ],
            [
                'methodName' => 'query',
                'args' => [$this->createMock(DiInterface::class)],
                'returnData' => $this->createMock(Criteria::class),
            ],
            [
                'methodName' => 'count',
                'args' => $defaultArgs,
                'returnData' => 10,
            ],
            [
                'methodName' => 'sum',
                'args' => $defaultArgs,
                'returnData' => null,
            ],
            [
                'methodName' => 'sum',
                'args' => $defaultArgs,
                'returnData' => '11.4',
            ],
            [
                'methodName' => 'average',
                'args' => $defaultArgs,
                'returnData' => null,
            ],
            [
                'methodName' => 'average',
                'args' => $defaultArgs,
                'returnData' => '20.42',
            ],
        ];
    }
}
