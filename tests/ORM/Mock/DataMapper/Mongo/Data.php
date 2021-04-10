<?php

declare(strict_types=1);

namespace Tests\ORM\Mock\DataMapper\Mongo;

use Owl\DataMapper\Mongo\Data as BaseData;

class Data extends BaseData
{
    protected static $mapper = Mapper::class;
    protected static $attributes = [
        '_id' => ['primary_key' => true, 'auto_generate' => true],
    ];
}
