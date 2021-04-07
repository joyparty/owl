<?php

declare(strict_types=1);

namespace Tests\MVC\Http;

use Owl\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test()
    {
        $response = new Response();

        // status
        $this->assertSame(200, $response->getStatusCode());

        $response->withStatus(201);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created', $response->getReasonPhrase());

        $response->withStatus(201, 'Done');
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Done', $response->getReasonPhrase());

        // attributes
        $response->withAttribute('foo', 1);
        $response->withAttribute('bar', 2);
        $this->assertSame(1, $response->getAttribute('foo'));
        $this->assertSame(2, $response->getAttribute('bar'));
        $this->assertSame(['foo' => 1, 'bar' => 2], $response->getAttributes());

        $response->withoutAttribute('foo');
        $this->assertSame(['bar' => 2], $response->getAttributes());

        // body
        $response->write('foo');
        $this->assertSame('foo', (string) $response->getBody());

        $response->write('bar');
        $this->assertSame('foobar', (string) $response->getBody());
    }
}
