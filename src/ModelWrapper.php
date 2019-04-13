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
     * @param mixed $parameters
     */
    public function find($parameters = null): SimpleResultset
    {
        return $this->modelName::find($parameters);
    }

    /**
     * @param mixed $parameters
     *
     * @return Model|false
     */
    public function findFirst($parameters = null)
    {
        return $this->modelName::findFirst($parameters);
    }

    public function query(?DiInterface $dependencyInjector = null): Criteria
    {
        return $this->modelName::query($dependencyInjector);
    }
}
