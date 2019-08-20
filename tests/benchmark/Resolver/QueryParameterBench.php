<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Benchmark\Resolver;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use TZachi\PhalconRepository\Resolver\Parameter;
use TZachi\PhalconRepository\Resolver\QueryParameter;

/**
 * @BeforeMethods({"setUp"})
 * @Revs(1000)
 * @Iterations(5)
 */
final class QueryParameterBench
{
    /**
     * @var QueryParameter
     */
    private $queryParameter;

    public function setUp(): void
    {
        $this->queryParameter = new QueryParameter();
    }

    public function benchWhere(): void
    {
        $this->queryParameter->where([
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
        ]);
    }
}
