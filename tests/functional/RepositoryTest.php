<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Functional;

use Faker\Factory;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultset;
use TZachi\PhalconRepository\ModelWrapper;
use TZachi\PhalconRepository\Repository;
use TZachi\PhalconRepository\Tests\Mock\Model\Payment;
use TZachi\PhalconRepository\Tests\Mock\Model\User;
use function current;
use function next;
use function range;
use function reset;

/**
 * @coversDefaultClass Repository
 */
final class RepositoryTest extends TestCase
{
    /**
     * @var User[] $users
     */
    private static $users;

    /**
     * @var Payment[] $users
     */
    private static $payments;

    /**
     * @var Repository
     */
    private $userRepository;

    /**
     * @var Repository
     */
    private $paymentRepository;

    /**
     * @beforeClass
     */
    public static function setUpDatabase(): void
    {
        self::setUpDi();

        self::resetTable('users');
        self::resetTable('payments');

        $faker = Factory::create();

        for ($i = 1; $i <= 30; $i++) {
            $user            = new User();
            $user->id        = $i;
            $user->name      = $faker->unique()->name;
            $user->email     = $faker->unique()->email;
            $user->createdAt = $faker->dateTimeBetween('-1 month')->format('Y-m-d H:i:s');
            $user->save();

            self::$users[$i] = $user;
        }

        for ($i = 1; $i < 10; $i++) {
            $payment            = new Payment();
            $payment->id        = $i;
            $payment->value     = 1.15 * $i;
            $payment->count     = $i % 5;
            $payment->createdAt = $faker->dateTimeBetween('-1 month')->format('Y-m-d H:i:s');
            $payment->save();

            self::$payments[$i] = $payment;
        }
    }

    /**
     * @before
     */
    public function setUpDependencies(): void
    {
        self::resetModelsMetadata();

        $this->userRepository    = new Repository(new ModelWrapper(User::class));
        $this->paymentRepository = new Repository(new ModelWrapper(Payment::class));
    }

    /**
     * @test
     */
    public function findFirstShouldReturnModel(): void
    {
        $user = $this->userRepository->findFirst(1);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[1]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstShouldReturnNullWhenIdNotFound(): void
    {
        self::assertNull($this->userRepository->findFirst(100));
    }

    /**
     * @test
     */
    public function findFirstByShouldReturnModel(): void
    {
        $user = $this->userRepository->findFirstBy('email', self::$users[10]->email);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[10]->toArray(), $user->toArray());

        $user = $this->userRepository->findFirstBy('id', [3, 4, 5], ['id' => 'DESC']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[5]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstWhereShouldReturnModel(): void
    {
        $conditions = [
            '@type' => Repository::TYPE_OR,
            'name' => self::$users[26]->name,
            'email' => self::$users[28]->email,
        ];

        $user = $this->userRepository->findFirstWhere($conditions, ['id']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[26]->toArray(), $user->toArray());

        $user = $this->userRepository->findFirstWhere($conditions, ['id' => 'DESC']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[28]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstWhereShouldReturnNullWithInvalidWhere(): void
    {
        $conditions = [
            '@type' => Repository::TYPE_AND,
            'name' => self::$users[26]->name,
            'email' => self::$users[28]->email,
        ];

        self::assertNull($this->userRepository->findFirstWhere($conditions));
    }

    /**
     * @test
     */
    public function findAllShouldReturnAllRows(): void
    {
        $this->compareResultSet($this->userRepository->findAll(), self::$users);
    }

    /**
     * @test
     */
    public function findByShouldReturnCorrectResultSet(): void
    {
        $emails    = [
            self::$users[12]->email,
            self::$users[22]->email,
            self::$users[25]->email,
        ];
        $resultSet = $this->userRepository->findBy('email', $emails, ['id' => 'DESC'], 2);

        $this->compareResultSet($resultSet, $this->getUsersSlice([25, 22]));
    }

    /**
     * @test
     */
    public function findWhereShouldReturnCorrectResultSetWithComplexCondition(): void
    {
        $resultSet = $this->userRepository->findWhere(
            [
                '@type' => Repository::TYPE_OR,
                [
                    '@operator' => 'BETWEEN',
                    'id' => [15, 21],
                ],
                'id' => range(5, 9),
                'name' => self::$users[11]->name,
            ],
            ['id' => 'DESC'],
            7,
            4
        );

        $this->compareResultSet($resultSet, $this->getUsersSlice([17, 16, 15, 11, 9, 8, 7]));
    }

    /**
     * @test
     */
    public function countShouldReturnNumberOfRows(): void
    {
        self::assertSame(30, $this->userRepository->count());
        self::assertSame(
            10,
            $this->userRepository->count(null, ['id' => [11, 20], '@operator' => 'BETWEEN'])
        );
    }

    /**
     * @test
     */
    public function sumShouldReturnSumOnColumn(): void
    {
        self::assertSame(20., $this->paymentRepository->sum('count'));
        self::assertSame(
            6.9,
            $this->paymentRepository->sum('value', ['id' => [1, 3], '@operator' => 'BETWEEN'])
        );
    }

    /**
     * @test
     */
    public function averageShouldReturnAverageOnColumn(): void
    {
        self::assertSame(
            8.05,
            $this->paymentRepository->average('value', ['id' => [5, 9], '@operator' => 'BETWEEN'])
        );
    }

    /**
     * @param int[] $ids
     *
     * @return User[]
     */
    protected function getUsersSlice(array $ids): array
    {
        $slice = [];
        foreach ($ids as $id) {
            $slice[$id] = self::$users[$id];
        }

        return $slice;
    }

    /**
     * @param User[] $expectedUsers
     */
    protected function compareResultSet(SimpleResultset $resultSet, array $expectedUsers): void
    {
        reset($expectedUsers);
        $resultSet->rewind();
        while ($resultSet->valid()) {
            /**
             * @var User $user
             */
            $user = $resultSet->current();
            self::assertInstanceOf(User::class, $user);
            self::assertSame(current($expectedUsers)->toArray(), $user->toArray());

            next($expectedUsers);
            $resultSet->next();
        }
    }
}
