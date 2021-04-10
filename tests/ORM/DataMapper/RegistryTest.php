<?php

declare(strict_types=1);

namespace Tests\ORM\DataMapper;

use Owl\DataMapper\Registry;
use PHPUnit\Framework\TestCase;
use Tests\ORM\Mock\DataMapper\Data;

class RegistryTest extends TestCase
{
    protected $class = Data::class;

    protected function setAttributes(array $attributes)
    {
        ($this->class)::getMapper()->setAttributes($attributes);
    }

    public function test()
    {
        $this->setAttributes([
            'id' => ['type' => 'uuid', 'primary_key' => true],
        ]);
        $id = '710c825e-20ea-4c98-b313-30d9eec2b2dc';

        $class = $this->class;
        $data = new $class([
            'id' => $id,
        ]);

        $registry = Registry::getInstance();

        $this->assertFalse((bool) $registry->get($class, $data->id(true)));
        $data->save();
        $this->assertFalse((bool) $registry->get($class, $data->id(true)));

        $data = $class::find($id);
        $this->assertTrue((bool) $registry->get($class, $data->id(true)));

        $data->destroy();
        $this->assertFalse((bool) $registry->get($class, ['id' => $id]));
    }
}
