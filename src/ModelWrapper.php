<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository;

use Phalcon\DiInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultset;

/**
 * Wrapper class for calling \Phalcon\Mvc\Model's static methods as instance methods without getting PHP warnings
 */
class ModelWrapper
{
    /**
     * @var string
     */
    protected $modelName;

    public function __construct(string $modelName)
    {
        $this->modelName = $modelName;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * @param mixed[] $parameters
     *
     * @return Model|false
     */
    public function findFirst(?array $parameters = null)
    {
        return $this->modelName::findFirst($parameters);
    }

    /**
     * @param mixed[] $parameters
     */
    public function find(?array $parameters = null): SimpleResultset
    {
        return $this->modelName::find($parameters);
    }

    /**
     * Generates a criteria for custom queries
     */
    public function query(?DiInterface $dependencyInjector = null): Criteria
    {
        return $this->modelName::query($dependencyInjector);
    }

    /**
     * @param mixed[] $parameters
     */
    public function count(?array $parameters = null): int
    {
        return $this->modelName::count($parameters);
    }

    /**
     * @param mixed[] $parameters
     */
    public function sum(?array $parameters = null): ?string
    {
        return $this->modelName::sum($parameters);
    }

    /**
     * @param mixed[] $parameters
     */
    public function average(?array $parameters = null): ?string
    {
        return $this->modelName::average($parameters);
    }

    /**
     * @param mixed[] $parameters
     */
    public function minimum(?array $parameters = null): ?string
    {
        return $this->modelName::minimum($parameters);
    }

    /**
     * @param mixed[] $parameters
     */
    public function maximum(?array $parameters = null): ?string
    {
        return $this->modelName::maximum($parameters);
    }
}
