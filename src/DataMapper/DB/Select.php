<?php

namespace Owl\DataMapper\DB;

use Owl\Service\DB\Select as ServiceSelect;

class Select extends ServiceSelect
{
    /**
     * @param int|null $limit
     *
     * @return Data[]
     */
    public function get($limit = null): array
    {
        $result = [];

        foreach (parent::get($limit) as $data) {
            $result[$data->id()] = $data;
        }

        return $result;
    }
}
