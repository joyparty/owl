<?php

namespace Owl\DataMapper\Mongo;

use Owl\DataMapper\Data as BaseData;
use Owl\DataMapper\Mongo\Mapper as MongoMapper;

/**
 * @method static MongoMapper getMapper()
 */
class Data extends BaseData
{
    protected static $mapper = MongoMapper::class;

    public static function query($expr)
    {
        return static::getMapper()->query($expr);
    }

    public static function iterator($expr = null)
    {
        return static::getMapper()->iterator($expr);
    }
}
