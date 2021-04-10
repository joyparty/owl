<?php

declare(strict_types=1);

namespace Tests\ORM\Mock\DataMapper\Mongo;

use Owl\DataMapper\Mongo\Mapper as BaseMapper;

class Mapper extends BaseMapper
{
    public function setAttributes(array $attributes)
    {
        $options = $this->getOptions();
        $options['attributes'] = $attributes;

        $this->options = $this->normalizeOptions($options);
    }
}
