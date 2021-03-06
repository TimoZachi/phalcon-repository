<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultset;
use TZachi\PhalconRepository\Resolver\Parameter as ParameterResolver;

class Repository
{
    /**
     * @var ModelWrapper
     */
    protected $modelWrapper;

    /**
     * @var ParameterResolver
     */
    protected $parameterResolver;

    /**
     * @var string
     */
    protected $idField;

    public function __construct(
        ModelWrapper $modelWrapper,
        ParameterResolver $parameterResolver,
        string $idField = 'id'
    ) {
        $this->modelWrapper      = $modelWrapper;
        $this->parameterResolver = $parameterResolver;
        $this->idField           = $idField;
    }

    /**
     * Returns the first record matching the specified conditions
     *
     * @param mixed[]  $where   Where condition
     * @param string[] $orderBy One or more order by statements
     */
    public function findFirstWhere(array $where = [], array $orderBy = []): ?Model
    {
        $parameters = $this->parameterResolver->where($where) + $this->parameterResolver->orderBy($orderBy);

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
    public function findWhere(array $where = [], array $orderBy = [], int $limit = 0, int $offset = 0): SimpleResultset
    {
        $parameters = $this->parameterResolver->where($where)
            + $this->parameterResolver->orderBy($orderBy)
            + $this->parameterResolver->limit($limit, $offset);

        return $this->modelWrapper->find($parameters);
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
        $parameters = [];
        if ($column !== null) {
            $parameters = $this->parameterResolver->column($column);
        }
        $parameters += $this->parameterResolver->where($where);

        return $this->modelWrapper->count($parameters);
    }

    /**
     * Returns the sum on a column of rows or null if the conditions don't match any rows
     *
     * @param mixed[] $where
     */
    public function sum(string $column, array $where = []): ?float
    {
        $parameters = $this->parameterResolver->column($column) + $this->parameterResolver->where($where);

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
        $parameters = $this->parameterResolver->column($column) + $this->parameterResolver->where($where);

        $average = $this->modelWrapper->average($parameters);
        if ($average === null) {
            return null;
        }

        return (float) $average;
    }

    /**
     * Returns the minimum on a column of rows or null if the conditions don't match any rows
     *
     * @param mixed[] $where
     */
    public function minimum(string $column, array $where = []): ?string
    {
        $parameters = $this->parameterResolver->column($column) + $this->parameterResolver->where($where);

        return $this->modelWrapper->minimum($parameters);
    }

    /**
     * Returns the maximum on a column of rows or null if the conditions don't match any rows
     *
     * @param mixed[] $where
     */
    public function maximum(string $column, array $where = []): ?string
    {
        $parameters = $this->parameterResolver->column($column) + $this->parameterResolver->where($where);

        return $this->modelWrapper->maximum($parameters);
    }

    /**
     * Returns the current parameter resolver that is being used
     */
    public function getParameterResolver(): ParameterResolver
    {
        return $this->parameterResolver;
    }
}
