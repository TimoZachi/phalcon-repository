<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Functional;

use Faker\Factory;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultset;
use TZachi\PhalconRepository\ModelWrapper;
use TZachi\PhalconRepository\Repository;
use TZachi\PhalconRepository\Resolver\QueryParameter;
use TZachi\PhalconRepository\Tests\Mock\Model\Payment;
use TZachi\PhalconRepository\Tests\Mock\Model\User;
use function array_reduce;
use function count;
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

        // Seed users table
        for ($i = 1; $i <= 30; $i++) {
            $user            = new User();
            $user->id        = $i;
            $user->name      = $faker->unique()->name;
            $user->email     = $faker->unique()->email;
            $user->createdAt = $faker->dateTimeBetween('-1 month')->format('Y-m-d H:i:s');
            $user->save();

            self::$users[$i] = $user;
        }

        // Seed payments table
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

        $this->userRepository    = new Repository(new ModelWrapper(User::class), new QueryParameter());
        $this->paymentRepository = new Repository(new ModelWrapper(Payment::class), new QueryParameter());
    }

    /**
     * @test
     */
    public function findFirstShouldReturnModelThatMatchesId(): void
    {
        $user = $this->userRepository->findFirst(1);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[1]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstShouldReturnNullWhenIdCannotBeFound(): void
    {
        self::assertNull($this->userRepository->findFirst(100));
    }

    /**
     * @test
     */
    public function findFirstByShouldReturnModelThatMatchesCondition(): void
    {
        $user = $this->userRepository->findFirstBy('email', self::$users[10]->email);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[10]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstByShouldOrderResults(): void
    {
        $user = $this->userRepository->findFirstBy('id', [3, 4, 5], ['id' => 'DESC']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[5]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstWhereShouldReturnModelThatMatchesCondition(): void
    {
        $conditions = [
            '@type' => QueryParameter::TYPE_OR,
            'name' => self::$users[26]->name,
            'email' => self::$users[28]->email,
        ];

        $user = $this->userRepository->findFirstWhere($conditions, ['id']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[26]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstWhereShouldOrderResults(): void
    {
        $user = $this->userRepository->findFirstWhere(['id' => range(8, 21)], ['id' => 'DESC']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(self::$users[21]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstWhereShouldReturnNullWhenConditionDoesNotMatchAnyRows(): void
    {
        $conditions = [
            '@type' => QueryParameter::TYPE_AND,
            'name' => self::$users[26]->name, // Switched name with email
            'email' => self::$users[28]->email,
        ];

        self::assertNull($this->userRepository->findFirstWhere($conditions));
    }

    /**
     * @test
     */
    public function findByShouldReturnCorrectResultSetThatMatchesCondition(): void
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
    public function findWhereShouldReturnCorrectResultSetThatMatchesCondition(): void
    {
        $resultSet = $this->userRepository->findWhere(
            [
                '@type' => QueryParameter::TYPE_OR,
                [
                    '@operator' => 'BETWEEN',
                    'id' => [16, 21],
                ],
                'name' => self::$users[14]->name,
                'email' => self::$users[13]->email,
                'id' => range(3, 10),
            ],
            ['id' => 'DESC'],
            8,
            4
        );

        $this->compareResultSet($resultSet, $this->getUsersSlice([17, 16, 14, 13, 10, 9, 8, 7]));
    }

    /**
     * @test
     */
    public function countShouldCountNumberOfRowsInTable(): void
    {
        self::assertSame(30, $this->userRepository->count());
    }

    /**
     * @test
     */
    public function countShouldReturnExactCountThatMatchesCondition(): void
    {
        self::assertSame(
            10,
            $this->userRepository->count(null, ['id' => [11, 20], '@operator' => 'BETWEEN'])
        );
    }

    /**
     * @test
     */
    public function sumShouldReturnSumOfAllRowsInAColumn(): void
    {
        self::assertSame(20., $this->paymentRepository->sum('count'));
    }

    /**
     * @test
     */
    public function sumShouldReturnExactSumThatMatchesCondition(): void
    {
        self::assertSame(
            6.9,
            $this->paymentRepository->sum('value', ['id' => [1, 3], '@operator' => 'BETWEEN'])
        );
    }

    /**
     * @test
     */
    public function sumShouldReturnNullWhenConditionDoesNotMatchAnyRows(): void
    {
        self::assertNull($this->paymentRepository->sum('value', ['id' => 10, '@operator' => '>']));
    }

    /**
     * @test
     */
    public function averageShouldCalculateTheAverageOfAllRowsInAColumn(): void
    {
        self::assertSame(5.75, $this->paymentRepository->average('value'));
    }

    /**
     * @test
     */
    public function averageShouldReturnExactAverageThatMatchesCondition(): void
    {
        self::assertSame(
            8.05,
            $this->paymentRepository->average('value', ['id' => [5, 9], '@operator' => 'BETWEEN'])
        );
    }

    /**
     * @test
     */
    public function averageShouldReturnNullWhenConditionDoesNotMatchAnyRows(): void
    {
        self::assertNull($this->paymentRepository->average('value', ['id' => 10, '@operator' => '>']));
    }

    /**
     * @test
     */
    public function minimumShouldReturnMinimumOfAllRowsInAColumn(): void
    {
        self::assertSame('1.15', $this->paymentRepository->minimum('value'));
        self::assertSame('0', $this->paymentRepository->minimum('count'));
    }

    /**
     * @test
     */
    public function minimumShouldReturnMinimumOfRowsThatMatchesCondition(): void
    {
        $users            = $this->getUsersSlice(range(1, 10));
        $minimumCreatedAt = array_reduce(
            $users,
            static function (?string $carry, User $user): string {
                if ($carry === null || $user->createdAt < $carry) {
                    return $user->createdAt;
                }

                return $carry;
            }
        );
        self::assertSame(
            $minimumCreatedAt,
            $this->userRepository->minimum('createdAt', ['id' => [1, 10], '@operator' => 'BETWEEN'])
        );
    }

    /**
     * @test
     */
    public function minimumShouldReturnNullWhenConditionDoesNotMatchAnyRows(): void
    {
        self::assertNull($this->userRepository->minimum('createdAt', ['id' => 0, '@operator' => '<']));
    }

    /**
     * @test
     */
    public function maximumShouldReturnMaximumOfAllRowsInAColumn(): void
    {
        self::assertSame('10.35', $this->paymentRepository->maximum('value'));
        self::assertSame('4', $this->paymentRepository->maximum('count'));
    }

    /**
     * @test
     */
    public function maximumShouldReturnMaximumOfRowsThatMatchesCondition(): void
    {
        $users            = $this->getUsersSlice(range(8, 21));
        $maximumCreatedAt = array_reduce(
            $users,
            static function (?string $carry, User $user): string {
                if ($carry === null || $user->createdAt > $carry) {
                    return $user->createdAt;
                }

                return $carry;
            }
        );
        self::assertSame(
            $maximumCreatedAt,
            $this->userRepository->maximum('createdAt', ['id' => [8, 21], '@operator' => 'BETWEEN'])
        );
    }

    /**
     * @test
     */
    public function maximumShouldReturnNullWhenConditionDoesNotMatchAnyRows(): void
    {
        self::assertNull($this->userRepository->maximum('createdAt', ['id' => 40, '@operator' => '>']));
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
        self::assertCount(count($expectedUsers), $resultSet);

        reset($expectedUsers);
        $resultSet->rewind();
        while ($resultSet->valid()) {
            /**
             * @var User $user
             */
            $user = $resultSet->current();
            self::assertInstanceOf(User::class, $user);
            self::assertSame(
                current($expectedUsers)->toArray(),
                $user->toArray()
            );

            next($expectedUsers);
            $resultSet->next();
        }
    }
}
