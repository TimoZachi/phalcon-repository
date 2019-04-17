<?php

declare(strict_types=1);

use Phalcon\Db\Column;
use Phalcon\Db\Index;

return [
    'columns' => [
        new Column(
            'id',
            [
                'type' => Column::TYPE_INTEGER,
                'unsigned' => true,
                'notNull' => true,
                'autoIncrement' => true,
                'size' => 10,
                'first' => true,
            ]
        ),
        new Column(
            'value',
            [
                'type' => Column::TYPE_FLOAT,
                'default' => '0.00',
                'notNull' => true,
                'size' => 8,
                'scale' => 2,
                'after' => 'id',
            ]
        ),
        new Column(
            'count',
            [
                'type' => Column::TYPE_INTEGER,
                'default' => '0',
                'unsigned' => true,
                'notNull' => true,
                'size' => 10,
                'after' => 'value',
            ]
        ),
        new Column(
            'created_at',
            [
                'type' => Column::TYPE_DATETIME,
                'size' => 1,
                'after' => 'count',
            ]
        ),
    ],
    'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
    ],
];
