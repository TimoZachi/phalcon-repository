<?php

declare(strict_types=1);

namespace TZachi\PhalconRepository\Tests\Mock\Model;

use Phalcon\Mvc\Model;

/**
 * Sample model for functional test cases
 *
 * @Source('payments')
 */
class Payment extends Model
{
    /**
     * @var int
     * @Primary
     * @Identity
     * @Column(type="integer", length=10, nullable=false)
     */
    public $id;

    /**
     * @var float
     * @Column(type="float", length=8, nullable=false)
     */
    public $value;

    /**
     * @var int
     * @Column(type="integer", length=10, nullable=false)
     */
    public $count;

    /**
     * @var string
     * @Column(type="string", length=19, column="created_at")
     */
    public $createdAt;

    public function getSource(): string
    {
        return 'payments';
    }
}
