<?php

namespace Owl\DataMapper\Type;

use Owl\Helpers\UUID as UUIDHelper;

class UUID extends Common
{
    public function normalizeAttribute(array $attribute)
    {
        $attribute = array_merge([
            'upper' => false,
        ], $attribute);

        if (isset($attribute['primary_key']) && $attribute['primary_key']) {
            $attribute['auto_generate'] = true;
        }

        return $attribute;
    }

    public function getDefaultValue(array $attribute)
    {
        if (!$attribute['auto_generate']) {
            return $attribute['default'];
        }

        $uuid = self::generate();

        if (isset($attribute['upper']) && $attribute['upper']) {
            $uuid = strtoupper($uuid);
        }

        return $uuid;
    }

    public static function generate(): string
    {
        return UUIDHelper::generateV4();
    }
}
