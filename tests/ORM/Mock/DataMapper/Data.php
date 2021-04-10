<?php

declare(strict_types=1);

namespace Tests\ORM\Mock\DataMapper;

use Owl\DataMapper\Data as BaseData;

class Data extends BaseData
{
    protected static $mapper = Mapper::class;

    protected static $mapper_options = [
        'service' => 'mock.storage',
        'collection' => 'mock.data',
    ];

    protected static $attributes = [
        'id' => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
    ];

    public static function setMapper(string $mapper_class)
    {
        static::$mapper = $mapper_class;
    }
}
