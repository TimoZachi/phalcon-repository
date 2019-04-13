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
            'name',
            [
                'type' => Column::TYPE_VARCHAR,
                'default' => '',
                'notNull' => true,
                'size' => 127,
                'after' => 'id',
            ]
        ),
        new Column(
            'email',
            [
                'type' => Column::TYPE_VARCHAR,
                'default' => '',
                'notNull' => true,
                'size' => 127,
                'after' => 'name',
            ]
        ),
        new Column(
            'created_at',
            [
                'type' => Column::TYPE_DATETIME,
                'size' => 1,
                'after' => 'email',
            ]
        ),
    ],
    'indexes' => [
        new Index('PRIMARY', ['id'], 'PRIMARY'),
    ],
];
