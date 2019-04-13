<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Mock\Model;

use Phalcon\Mvc\Model;

/**
 * Sample model for functional test cases
 *
 * @Source('users')
 */
class User extends Model
{
    /**
     * @var int
     * @Primary
     * @Identity
     * @Column(type="integer", length=10, nullable=false)
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $name;

    /**
     * @var string
     * @Column(type="string", length=127, nullable=false)
     */
    public $email;

    /**
     * @var string
     * @Column(type="string", length=127, column="created_at")
     */
    public $createdAt;

    public function getSource(): string
    {
        return 'users';
    }
}
