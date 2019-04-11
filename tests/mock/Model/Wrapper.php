<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Mock\Model;

use PHPUnit\Framework\TestCase;

/**
 * Test model for ModelWrapperTest
 */
final class Wrapper
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
        if ($name === self::$methodName) {
            TestCase::assertSame(self::$args, $arguments);

            return self::$returnValue;
        }

        return null;
    }
}
