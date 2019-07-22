<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Resolver;

use InvalidArgumentException;
use function array_keys;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function range;
use function sprintf;
use function strtoupper;

/**
 * Class to map query arguments to parameters that can be used in phalcon models
 */
class QueryParameter implements Parameter
{
    /**
     * @var int
     */
    protected $bindingIndex;

    /**
     * {@inheritdoc}
     */
    public function where(array $where, int $bindingStartIndex = 0): array
    {
        if ($where === []) {
            return [];
        }

        $this->bindingIndex = $bindingStartIndex;

        [$type, $operator] = $this->extractConditionConfig($where);
        $conditions        = [];
        $bindings          = [];

        foreach ($where as $field => $value) {
            $this->validateValueForOperator($operator, $value);

            if ($value === null) {
                $conditions[] = '[' . $field . '] IS NULL';
                continue;
            }

            if (is_array($value)) {
                $conditions[] = $this->createConditionsFromArray($operator, $field, $value, $bindings);
                continue;
            }

            $conditions[]                  = sprintf('[%s] %s ?%d', $field, $operator, $this->bindingIndex);
            $bindings[$this->bindingIndex] = $value;
            $this->bindingIndex++;
        }

        return [
            'conditions' => implode(' ' . $type . ' ', $conditions),
            'bind' => $bindings,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function orderBy(array $orderBy): array
    {
        if ($orderBy === []) {
            return [];
        }

        $orderByStatements = [];
        foreach ($orderBy as $sortField => $sortDirection) {
            if (is_int($sortField)) {
                $sortField     = $sortDirection;
                $sortDirection = 'ASC';
            } else {
                $sortDirection = strtoupper($sortDirection);
            }

            if ($sortDirection !== self::ORDER_ASC && $sortDirection !== self::ORDER_DESC) {
                throw new InvalidArgumentException(
                    sprintf('Sort direction must be one of the %s::ORDER_ constants', self::class)
                );
            }

            $orderByStatements[] = '[' . $sortField . '] ' . $sortDirection;
        }

        return ['order' => implode(', ', $orderByStatements)];
    }

    /**
     * {@inheritdoc}
     *
     * @return int[]
     */
    public function limit(int $limit, int $offset = 0): array
    {
        $parameters = [];
        if ($limit > 0) {
            $parameters['limit']  = $limit;
            $parameters['offset'] = $offset;
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function column(string $columnName): array
    {
        return ['column' => $columnName];
    }

    /**
     * @param mixed[] $where
     *
     * @return string[]
     *
     * @throws InvalidArgumentException When type or operator is invalid
     */
    protected function extractConditionConfig(array &$where): array
    {
        $type     = $where['@type'] ?? self::TYPE_AND;
        $operator = $where['@operator'] ?? '=';
        unset($where['@type'], $where['@operator']);

        if ($type !== self::TYPE_AND && $type !== self::TYPE_OR) {
            throw new InvalidArgumentException(
                sprintf('configuration @type must be one of the %s::TYPE_ constants', self::class)
            );
        }

        if (!in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException(
                sprintf('configuration @operator must be one of: %s', implode(', ', self::OPERATORS))
            );
        }

        return [$type, $operator];
    }

    /**
     * @param mixed $value
     */
    protected function validateValueForOperator(string $operator, $value): void
    {
        if (is_array($value)) {
            if (!in_array($operator, ['=', '<>', 'BETWEEN'], true)) {
                throw new InvalidArgumentException('Operator ' . $operator . ' cannot have an array as its value');
            }

            return;
        }
        if ($operator === 'BETWEEN') {
            throw new InvalidArgumentException('Operator BETWEEN needs an array as its value');
        }
    }

    /**
     * @param string|int $field
     * @param mixed[]    $value
     * @param mixed[]    $bindings
     *
     * @throws InvalidArgumentException When value is an empty array
     */
    protected function createConditionsFromArray(string $operator, $field, array $value, array &$bindings): string
    {
        if ($value === []) {
            throw new InvalidArgument('Empty array value is not allowed in where condition');
        }

        if ($operator === 'BETWEEN') {
            return sprintf('[%s] BETWEEN %s', $field, $this->createBetweenCondition($value, $bindings));
        }

        // Check if $value is not an indexed array
        if (array_keys($value) !== range(0, count($value) - 1)) {
            $parameters = $this->where($value, $this->bindingIndex);
            $bindings  += $parameters['bind'];

            return '(' . $parameters['conditions'] . ')';
        }

        return sprintf(
            '[%s] %sIN (%s)',
            $field,
            $operator === '=' ? '' : 'NOT ',
            $this->createInCondition($value, $bindings)
        );
    }

    /**
     * @param mixed[]  $value
     * @param string[] $bindings
     *
     * @throws InvalidArgumentException When value is not an array with two values
     */
    protected function createBetweenCondition(array $value, array &$bindings): string
    {
        if (count($value) !== 2) {
            throw new InvalidArgumentException(
                'Value for BETWEEN operator must be an array with exactly two values'
            );
        }

        $condition                       = sprintf('?%d AND ?%d', $this->bindingIndex, $this->bindingIndex + 1);
        $bindings[$this->bindingIndex++] = $value[0];
        $bindings[$this->bindingIndex++] = $value[1];

        return $condition;
    }

    /**
     * @param mixed[]  $values
     * @param string[] $bindings
     */
    protected function createInCondition(array $values, array &$bindings): string
    {
        $condition = '';
        foreach ($values as $i => $value) {
            $condition                    .= sprintf('%s?%d', $i === 0 ? '' : ', ', $this->bindingIndex);
            $bindings[$this->bindingIndex] = $value;

            $this->bindingIndex++;
        }

        return $condition;
    }
}
