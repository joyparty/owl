<?php

namespace Owl\Helpers;

class UUID
{
    /**
     * 生成V4版UUID.
     *
     * @see http://www.rfcreader.com/#rfc4122_line630
     */
    public static function generateV4(): string
    {
        $uuid = bin2hex(random_bytes(18));
        $uuid[8] = $uuid[13] = $uuid[18] = $uuid[23] = '-';
        $uuid[14] = '4';
        $uuid[19] = dechex(hexdec($uuid[19]) & 3 | 8);

        return $uuid;
    }
}
