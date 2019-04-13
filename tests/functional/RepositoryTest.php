<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Functional;

use Faker\Factory;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultset;
use TZachi\PhalconRepository\ModelWrapper;
use TZachi\PhalconRepository\Repository;
use TZachi\PhalconRepository\Tests\Mock\Model\User;
use function current;
use function next;
use function range;
use function reset;

final class RepositoryTest extends TestCase
{
    /**
     * @var User[] $users
     */
    private $users;

    /**
     * @var Repository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        if ($this->users !== null) {
            return;
        }

        self::setUpDi();
        self::resetTable('users');

        $faker = Factory::create();

        for ($i = 1; $i <= 30; $i++) {
            $user            = new User();
            $user->id        = $i;
            $user->name      = $faker->unique()->name;
            $user->email     = $faker->unique()->email;
            $user->createdAt = $faker->dateTimeBetween('-1 month')->format('Y-m-d H:i:s');
            $user->save();

            $this->users[$i] = $user;
        }

        $this->repository = new Repository(new ModelWrapper(User::class));
    }

    /**
     * @test
     */
    public function findFirstShouldReturnModel(): void
    {
        $user = $this->repository->findFirst(1);
        self::assertInstanceOf(User::class, $user);
        self::assertSame($this->users[1]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstShouldReturnNullWhenIdNotFound(): void
    {
        self::assertNull($this->repository->findFirst(100));
    }

    /**
     * @test
     */
    public function findFirstByShouldReturnModel(): void
    {
        $user = $this->repository->findFirstBy('email', $this->users[10]->email);
        self::assertInstanceOf(User::class, $user);
        self::assertSame($this->users[10]->toArray(), $user->toArray());

        $user = $this->repository->findFirstBy('id', [3, 4, 5], ['id' => 'DESC']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame($this->users[5]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstWhereShouldReturnModel(): void
    {
        $conditions = [
            '@type' => Repository::TYPE_OR,
            'name' => $this->users[26]->name,
            'email' => $this->users[28]->email,
        ];

        $user = $this->repository->findFirstWhere($conditions, ['id']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame($this->users[26]->toArray(), $user->toArray());

        $user = $this->repository->findFirstWhere($conditions, ['id' => 'DESC']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame($this->users[28]->toArray(), $user->toArray());
    }

    /**
     * @test
     */
    public function findFirstWhereShouldReturnNullWithInvalidWhere(): void
    {
        $conditions = [
            '@type' => Repository::TYPE_AND,
            'name' => $this->users[26]->name,
            'email' => $this->users[28]->email,
        ];

        self::assertNull($this->repository->findFirstWhere($conditions));
    }

    /**
     * @test
     */
    public function findByShouldReturnCorrectResultSet(): void
    {
        $emails    = [
            $this->users[12]->email,
            $this->users[22]->email,
            $this->users[25]->email,
        ];
        $resultSet = $this->repository->findBy('email', $emails, ['id' => 'DESC'], 2);

        $this->compareResultSet($resultSet, $this->getUsersSlice([25, 22]));
    }

    /**
     * @test
     */
    public function findWhereShouldReturnCorrectResultSetWithComplexCondition(): void
    {
        $resultSet = $this->repository->findWhere(
            [
                '@type' => Repository::TYPE_OR,
                [
                    '@operator' => 'BETWEEN',
                    'id' => [15, 21],
                ],
                'id' => range(5, 9),
                'name' => $this->users[11]->name,
            ],
            ['id' => 'DESC'],
            7,
            4
        );

        $this->compareResultSet($resultSet, $this->getUsersSlice([17, 16, 15, 11, 9, 8, 7]));
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
            $slice[$id] = $this->users[$id];
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
