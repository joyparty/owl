<?php

namespace Owl\Context;

use Owl\Context as BaseContext;

class Session extends BaseContext
{
    public function set($key, $val)
    {
        $token = $this->getToken();

        $_SESSION[$token][$key] = $val;
    }

    public function get($key = null)
    {
        $token = $this->getToken();
        $context = $_SESSION[$token] ?? [];

        return ($key === null)
             ? $context
             : ($context[$key] ?? null);
    }

    public function has($key)
    {
        $token = $this->getToken();

        return isset($_SESSION[$token][$key]);
    }

    public function remove($key)
    {
        $token = $this->getToken();

        unset($_SESSION[$token][$key]);
    }

    public function clear()
    {
        $token = $this->getToken();

        unset($_SESSION[$token]);
    }
}
