<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Functional;

use Phalcon\Annotations\AdapterInterface as AnnotationsAdapterInterface;
use Phalcon\Annotations\Factory as AnnotationsFactory;
use Phalcon\Db\Adapter\Pdo as PdoDbAdapter;
use Phalcon\Db\Adapter\Pdo\Sqlite as SqliteDbAdapter;
use Phalcon\Di;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\MetaData\Memory;
use Phalcon\Mvc\Model\MetaData\Strategy\Annotations as AnnotationsStrategy;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RuntimeException;
use function dirname;
use function sprintf;

/**
 * Base test case for functional tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * @var PdoDbAdapter|null
     */
    protected static $sharedConnection;

    /**
     * @var Di|null
     */
    protected static $sharedDi;

    protected static function setUpDi(): void
    {
        if (self::$sharedConnection === null) {
            self::$sharedConnection = new SqliteDbAdapter(['dbname' => __DIR__ . '/database.sqlite']);
        }

        if (self::$sharedDi !== null) {
            return;
        }

        $di = new Di();
        $di->setShared('db', self::$sharedConnection);
        $di->setShared(
            'modelsManager',
            function (): ModelsManager {
                return new ModelsManager();
            }
        );
        $di->setShared(
            'annotations',
            function (): AnnotationsAdapterInterface {
                return AnnotationsFactory::load(['adapter' => 'memory']);
            }
        );
        $di->setShared(
            'modelsMetadata',
            function (): Memory {
                $metadata = new Memory();
                $metadata->setStrategy(new AnnotationsStrategy());

                return $metadata;
            }
        );
        Di::setDefault($di);

        self::$sharedDi = $di;
    }

    protected static function resetModelsMetadata(): void
    {
        if (self::$sharedDi === null) {
            throw new RuntimeException(
                sprintf('Please call %s::setUpDi before calling %s', self::class, __METHOD__)
            );
        }

        /**
         * @var Memory $metadata
         */
        $metadata = self::$sharedDi->get('modelsMetadata');
        $metadata->reset();
    }

    protected static function resetTable(string $tableName): bool
    {
        if (self::$sharedConnection === null) {
            throw new RuntimeException(
                sprintf('Please call %s::setUpDi before calling %s', self::class, __METHOD__)
            );
        }

        self::$sharedConnection->dropTable($tableName);

        return self::$sharedConnection->createTable(
            $tableName,
            null,
            require dirname(__DIR__) . '/migrations/' . $tableName . '.php'
        );
    }
}
