<?php

declare(strict_types=1);

namespace Tests\MVC;

use Owl\Application;
use Owl\Http\Request;
use Owl\Http\Response;
use Owl\Http\Exception as HttpException;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function test501()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(501);

        $app = new Application();
        $app->setExceptionHandler(function ($exception) {
            throw $exception;
        });

        $request = Request::factory([
            'method' => 'FOO',
        ]);
        $response = new Response();

        $app->execute($request, $response);
    }
}
