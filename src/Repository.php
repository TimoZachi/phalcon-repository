<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository;

use InvalidArgumentException;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultset;
use function array_keys;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function range;
use function sprintf;
use function strtoupper;

class Repository
{
    public const TYPE_AND = 'AND';
    public const TYPE_OR  = 'OR';

    protected const OPERATORS = ['=', '<>', '<=', '>=','<', '>', 'BETWEEN'];

    protected const CONDITION_TYPES = [self::TYPE_AND, self::TYPE_OR];

    /**
     * @var ModelWrapper
     */
    protected $modelWrapper;

    /**
     * @var string
     */
    protected $idField;

    public function __construct(ModelWrapper $modelWrapper, string $idField = 'id')
    {
        $this->modelWrapper = $modelWrapper;
        $this->idField      = $idField;
    }

    /**
     * Returns the first record matching the specified conditions
     *
     * @param mixed[]  $where   Where condition
     * @param string[] $orderBy One or more order by statements
     */
    public function findFirstWhere(array $where, array $orderBy = []): ?Model
    {
        $parameters = array_merge($this->whereToParameters($where), $this->orderByToParameters($orderBy));

        $model = $this->modelWrapper->findFirst($parameters);
        if ($model === false) {
            return null;
        }

        return $model;
    }

    /**
     * Returns the first record that matches a single condition
     *
     * @param mixed    $value
     * @param string[] $orderBy One or more order by statements
     */
    public function findFirstBy(string $field, $value, array $orderBy = []): ?Model
    {
        return $this->findFirstWhere([$field => $value], $orderBy);
    }

    /**
     * Returns the first record that matches a primary key
     *
     * @param mixed $id
     */
    public function findFirst($id): ?Model
    {
        return $this->findFirstBy($this->idField, $id);
    }

    /**
     * Finds all records matching the specified conditions
     *
     * @param mixed[]  $where
     * @param string[] $orderBy
     */
    public function findWhere(array $where, array $orderBy = [], int $limit = 0, int $offset = 0): SimpleResultset
    {
        $parameters = array_merge($this->whereToParameters($where), $this->orderByToParameters($orderBy));
        if ($limit > 0) {
            $parameters['offset'] = $offset;
            $parameters['limit']  = $limit;
        }

        return $this->modelWrapper->find($parameters);
    }

    /**
     * Finds all records in a table
     *
     * @param string[] $orderBy
     */
    public function findAll(array $orderBy = [], int $limit = 0, int $offset = 0): SimpleResultset
    {
        return $this->findWhere([], $orderBy, $limit, $offset);
    }

    /**
     * Finds all records that match a single condition
     *
     * @param mixed    $value
     * @param string[] $orderBy
     */
    public function findBy(
        string $field,
        $value,
        array $orderBy = [],
        int $limit = 0,
        int $offset = 0
    ): SimpleResultset {
        return $this->findWhere([$field => $value], $orderBy, $limit, $offset);
    }

    /**
     * Returns a query builder (Criteria) to create custom queries
     */
    public function query(): Criteria
    {
        return $this->modelWrapper->query();
    }

    /**
     * Returns the number of rows that match a certain condition
     *
     * @param mixed[] $where
     */
    public function count(?string $column = null, array $where = []): int
    {
        $parameters = $this->whereToParameters($where);
        if ($column !== null) {
            $parameters['column'] = $column;
        }

        return $this->modelWrapper->count($parameters);
    }

    /**
     * Returns the sum on a column of rows or null if the conditions don't match any rows
     *
     * @param mixed[] $where
     */
    public function sum(string $column, array $where = []): ?float
    {
        $parameters = array_merge(['column' => $column], $this->whereToParameters($where));

        $sum = $this->modelWrapper->sum($parameters);
        if ($sum === null) {
            return null;
        }

        return (float) $sum;
    }

    /**
     * Returns the average on a column of rows or null if the conditions don't match any rows
     *
     * @param mixed[] $where
     */
    public function average(string $column, array $where = []): ?float
    {
        $parameters = array_merge(['column' => $column], $this->whereToParameters($where));

        $average = $this->modelWrapper->average($parameters);
        if ($average === null) {
            return null;
        }

        return (float) $average;
    }

    /**
     * @param mixed[] $where
     *
     * @return mixed[]
     *
     * @throws InvalidArgumentException When where contains invalid values.
     */
    public function whereToParameters(array $where, int &$paramsIdx = 0): array
    {
        if ($where === []) {
            return [];
        }

        $config     = $this->extractConfigFromWhere($where);
        $conditions = [];
        $bindings   = [];

        foreach ($where as $field => $value) {
            if ($value === null) {
                $conditions[] = '[' . $field . '] IS NULL';
                continue;
            }

            if (is_array($value)) {
                if ($value === []) {
                    throw new InvalidArgumentException('Empty array value is not allowed in where conditions');
                }

                // Check if $value is an associative array.
                if (array_keys($value) !== range(0, count($value) - 1)) {
                    $parameters = $this->whereToParameters($value, $paramsIdx);

                    $conditions[] = '(' . $parameters['conditions'] . ')';
                    $bindings    += $parameters['bind'];

                    continue;
                }

                if ($config['operator'] === 'BETWEEN') {
                    $conditions[] = sprintf(
                        '[%s] BETWEEN %s',
                        $field,
                        $this->createBetweenRange($value, $bindings, $paramsIdx)
                    );

                    continue;
                }

                $conditions[] = sprintf('[%s] IN (%s)', $field, $this->createValueList($value, $bindings, $paramsIdx));

                continue;
            }

            $conditions[]         = sprintf('[%s] %s ?%d', $field, $config['operator'], $paramsIdx);
            $bindings[$paramsIdx] = $value;
            $paramsIdx++;
        }

        return [
            'conditions' => implode(' ' . $config['type'] . ' ', $conditions),
            'bind' => $bindings,
        ];
    }

    /**
     * @param mixed[] $where
     *
     * @return string[]
     */
    protected function extractConfigFromWhere(array &$where): array
    {
        $config = [
            'type' => $where['@type'] ?? self::TYPE_AND,
            'operator' => $where['@operator'] ?? '=',
        ];

        if (!in_array($config['operator'], self::OPERATORS, true)) {
            throw new InvalidArgumentException('Operator ' . $config['operator'] . ' is not a valid operator');
        }

        foreach (array_keys($config) as $key) {
            unset($where['@' . $key]);
        }

        return $config;
    }

    /**
     * @param mixed[]  $values
     * @param string[] $bindings
     */
    protected function createBetweenRange(array $values, array &$bindings, int &$paramsIdx): string
    {
        if (count($values) !== 2) {
            throw new InvalidArgumentException(
                'Value for between operator must be an array with exactly two values'
            );
        }

        $range                    = sprintf('?%d AND ?%d', $paramsIdx, $paramsIdx + 1);
        $bindings[$paramsIdx]     = $values[0];
        $bindings[$paramsIdx + 1] = $values[1];

        $paramsIdx += 2;

        return $range;
    }

    /**
     * @param mixed[]  $values
     * @param string[] $bindings
     */
    protected function createValueList(array $values, array &$bindings, int &$paramsIdx): string
    {
        $list = '';
        foreach ($values as $i => $value) {
            $list                .= sprintf('%s?%d', $i === 0 ? '' : ', ', $paramsIdx);
            $bindings[$paramsIdx] = $value;

            $paramsIdx++;
        }

        return $list;
    }

    /**
     * @param string[] $orderBy
     *
     * @return string[]
     */
    public function orderByToParameters(array $orderBy): array
    {
        if ($orderBy === []) {
            return [];
        }

        $orderByStatements = [];
        foreach ($orderBy as $sortField => $origSortOrder) {
            $sortOrder = 'ASC';
            if (strtoupper($origSortOrder) === 'DESC') {
                $sortOrder = 'DESC';
            }

            if (is_int($sortField)) {
                $sortField = $origSortOrder;
            }

            $orderByStatements[] = '[' . $sortField . '] ' . $sortOrder;
        }

        return ['order' => implode(', ', $orderByStatements)];
    }
}
