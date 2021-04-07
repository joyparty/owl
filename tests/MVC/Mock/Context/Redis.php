<?php

declare(strict_types=1);

namespace Tests\MVC\Mock\Context;

use Owl\Context\Redis as ContextRedis;

class Redis extends ContextRedis
{
    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function getTimeout()
    {
        $redis = $this->getService();
        $token = $this->getToken();

        return $redis->ttl($token);
    }
}
