<?php

declare(strict_types=1);

namespace Tests\ORM\Mock\DataMapper;

use Owl\DataMapper\Data as BaseData;
use Owl\DataMapper\Mapper as BaseMapper;
use Owl\Service;

class Mapper extends BaseMapper
{
    public function setAttributes(array $attributes)
    {
        $options = $this->getOptions();
        $options['attributes'] = $attributes;

        $this->options = $this->normalizeOptions($options);
    }

    protected function doFind(array $id, Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        return $service->find($collection, $id);
    }

    protected function doInsert(BaseData $data, Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data);

        if (!$service->insert($collection, $record, $data->id(true))) {
            return false;
        }

        $id = [];
        foreach ($this->getPrimaryKey() as $key) {
            if (!isset($record[$key])) {
                if (!$last_id = $service->getLastId($collection, $key)) {
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

        return $service->update($collection, $record, $data->id(true));
    }

    protected function doDelete(BaseData $data, Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        return $service->delete($collection, $data->id(true));
    }
}
