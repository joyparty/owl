<?php

namespace Owl\DataMapper\DB;

use Owl\DataMapper\Data as BaseData;
use Owl\DataMapper\Mapper as BaseMapper;
use Owl\Service;
use Owl\Service\DB\Adapter;
use Owl\Service\DB\Expr;
use Owl\Service\DB\Select as ServiceSelect;

class Mapper extends BaseMapper
{
    /**
     * @param Service|null $service
     * @param string|Expr|ServiceSelect|null $collection
     *
     * @return Select|ServiceSelect
     */
    public function select(Service $service = null, $collection = null)
    {
        /** @var Adapter $service */
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $primary_key = $this->getPrimaryKey();

        // 只有一个主键，就可以返回以主键为key的数组结果
        if (1 === count($primary_key)) {
            $select = new Select($service, $collection);
        } else {
            $select = new ServiceSelect($service, $collection);
        }

        $select->setColumns(array_keys($this->getAttributes()));

        $mapper = $this;
        $select->setProcessor(function ($record) use ($mapper) {
            return $record ? $mapper->pack($record) : false;
        });

        return $select;
    }

    public function getBySQLAsIterator($sql, array $parameters = [], Service $service = null)
    {
        /** @var Adapter $service */
        $service = $service ?: $this->getService();
        $res = $service->execute($sql, $parameters);

        while ($record = $res->fetch()) {
            yield $this->pack($record);
        }
    }

    protected function doFind(array $id, Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        $select = $this->select($service, $collection);

        list($where, $params) = $this->whereID($service, $id);
        $select->where($where, $params);

        return $select->limit(1)->execute()->fetch();
    }

    protected function doInsert(BaseData $data, Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data);

        if (!$service->insert($collection, $record)) {
            return false;
        }

        $id = [];
        foreach ($this->getPrimaryKey() as $key) {
            if (!isset($record[$key])) {
                if (!$last_id = $service->lastId($collection, $key)) {
                    throw new \Exception("{$this->class}: Insert record success, but get last-id failed!");
                }
                $id[$key] = $last_id;
            }
        }

        return $id;
    }

    protected function doUpdate(BaseData $data, Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data, ['dirty' => true]);

        list($where, $params) = $this->whereID($service, $data->id(true));

        return $service->update($collection, $record, $where, $params);
    }

    protected function doDelete(BaseData $data, Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        list($where, $params) = $this->whereID($service, $data->id(true));

        return $service->delete($collection, $where, $params);
    }

    protected function whereID(Service $service, array $id)
    {
        $where = $params = [];
        $primary_key = $this->getPrimaryKey();

        foreach ($primary_key as $key) {
            $where[] = $service->quoteIdentifier($key) . ' = ?';
            $params[] = $id[$key];
        }
        $where = implode(' AND ', $where);

        return [$where, $params];
    }
}
