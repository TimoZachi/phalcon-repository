<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Test\Unit;

use Phalcon\DiInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\Model\ResultsetInterface;
use PHPUnit\Framework\TestCase;
use TZachi\PhalconRepository\ModelWrapper;
use function get_class;

class ModelWrapperTest extends TestCase
{
    /**
     * @test
     */
    public function constructorShouldAssignModelName(): void
    {
        $modelClass = Model::class;

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
        $model = new class()
        {
            /**
             * @var string
             */
            public static $methodName;

            /**
             * @var mixed[]
             */
            public static $args;

            /**
             * @var mixed
             */
            public static $returnValue;

            /**
             * @param mixed[] $arguments
             *
             * @return mixed
             */
            public static function __callStatic(string $name, array $arguments)
            {
                if ($name === static::$methodName) {
                    TestCase::assertSame(static::$args, $arguments);

                    return static::$returnValue;
                }

                return null;
            }
        };
        $class = get_class($model);

        $class::$methodName  = $methodName;
        $class::$args        = $args;
        $class::$returnValue = $returnValue;

        $modelWrapper = new ModelWrapper(get_class($model));

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
                'returnData' => $this->createMock(ResultsetInterface::class),
            ],
            [
                'methodName' => 'query',
                'args' => [$this->createMock(DiInterface::class)],
                'returnData' => $this->createMock(Criteria::class),
            ],
        ];
    }
}
