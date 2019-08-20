<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Resolver;

/**
 * Resolve repository parameters into parameters that are understandable by Phalcon models
 */
interface Parameter
{
    public const TYPE_AND = 'AND';
    public const TYPE_OR  = 'OR';

    public const ORDER_ASC  = 'ASC';
    public const ORDER_DESC = 'DESC';

    /**
     * List of possible conditions that can be used
     */
    public const CONDITION_TYPES = [self::TYPE_AND, self::TYPE_OR];

    /**
     * List of possible operators that can be used
     */
    public const OPERATORS = ['=', '<>', '<=', '>=', '<', '>', 'LIKE', 'BETWEEN'];

    /**
     * Converts condition parameters to be used to filter the result
     *
     * @param mixed[] $where             This should be an array with multiple key => value entries that should be
     *                                   converted to Phalcon parameters. It can also contain nested arrays with
     *                                   different conditions and operators inside, by using respectively the '@type'
     *                                   and '@operator' keys
     * @param int     $bindingStartIndex If specified, parameter binding will start from this index
     *
     * @return mixed[] The converted parameters
     *
     * @throws InvalidArgument When a condition contains an invalid value
     */
    public function where(array $where, int $bindingStartIndex = 0): array;

    /**
     * Converts an order column and direction to be used for sorting the result
     *
     * @param string[] $orderBy Should be an array with key => value entries to represent order => order_direction.
     *                          Only directions ASC and DESC should be allowed
     *
     * @return mixed[] The converted parameters
     *
     * @throws InvalidArgument When sort direction is invalid
     */
    public function orderBy(array $orderBy): array;

    /**
     * Converts offset and limit parameters to be used for limiting the result
     *
     * @param int $limit This should convert to a valid limit clause readable by Phalcon models
     *
     * @return mixed[] The resolved parameters
     */
    public function limit(int $limit, int $offset = 0): array;

    /**
     * Converts a column name to be used as an aggregated column
     *
     * @return string[] The resolved column name
     */
    public function column(string $columnName): array;
}
