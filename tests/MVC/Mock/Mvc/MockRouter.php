<?php

declare(strict_types=1);

namespace Tests\MVC\Mock\Mvc;

use Owl\Http\Request;
use Owl\Http\Response;
use Owl\Mvc\Router;

class MockRouter extends Router
{
    public function testExecute($path, $method = 'GET')
    {
        $request = Request::factory([
            'uri' => $path,
            'method' => $method,
        ]);

        return $this->execute($request, new Response());
    }

    public function testDispatch($path)
    {
        $request = Request::factory([
            'uri' => $path,
            'method' => 'GET',
        ]);

        if ($router = $this->getDelegateRouter($request)) {
            return $router->dispatch($request);
        }

        return $this->dispatch($request);
    }

    public function dispatch($request)
    {
        $path = $this->getRequestPath($request);

        return $this->byRewrite($path) ?: $this->byPath($path);
    }
}
