<?php

namespace Owl\DataMapper\DB;

use Owl\DataMapper\Data as BaseData;
use Owl\DataMapper\DB\Mapper as DBMapper;
use Owl\Service;

/**
 * @method static DBMapper getMapper()
 */
class Data extends BaseData
{
    protected static $mapper = DBMapper::class;

    /**
     * @return Select
     */
    public static function select()
    {
        return static::getMapper()->select();
    }

    /**
     * @param string $sql
     * @param array $parameters
     * @param Service|null $service
     *
     * @return self[]
     */
    public static function getBySQL($sql, array $parameters = [], Service $service = null): array
    {
        $result = [];

        foreach (static::getBySQLAsIterator($sql, $parameters, $service) as $data) {
            $id = $data->id();

            if (is_array($id)) {
                $result[] = $data;
            } else {
                $result[$id] = $data;
            }
        }

        return $result;
    }

    public static function getBySQLAsIterator($sql, array $parameters = [], Service $service = null)
    {
        return static::getMapper()->getBySQLAsIterator($sql, $parameters, $service);
    }
}
