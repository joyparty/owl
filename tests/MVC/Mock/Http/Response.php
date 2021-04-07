<?php

declare(strict_types=1);

namespace Tests\MVC\Mock\Http;

use Tests\MVC\Mock\Cookie as MockCookie;

class Response extends \Owl\Http\Response
{
    public function __construct()
    {
        $this->cookies = MockCookie::getInstance();
    }

    public function withCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = null, $httponly = true)
    {
        call_user_func_array([$this->cookies, 'set'], func_get_args());
    }

    public function getCookies()
    {
        return $this->cookies->get();
    }
}
