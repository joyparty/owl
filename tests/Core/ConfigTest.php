<?php

declare(strict_types=1);

namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use Owl\Config;

class ConfigTest extends TestCase
{
    public function test()
    {
        $config = [
            'foo' => [
                'bar' => 1,
            ],
        ];

        Config::merge($config);

        $this->assertSame($config, Config::get());
        $this->assertSame($config['foo'], Config::get('foo'));
        $this->assertSame($config['foo']['bar'], Config::get('foo', 'bar'));
        $this->assertSame(Config::get('foo', 'bar'), Config::get(['foo', 'bar']));
        $this->assertFalse(Config::get('foobar'));
    }
}
