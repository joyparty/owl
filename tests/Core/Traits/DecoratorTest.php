<?php

declare(strict_types=1);

namespace Tests\Core\Traits;

use PHPUnit\Framework\TestCase;

class DecoratorTest extends TestCase
{
    public function test()
    {
        $bar = new Bar();

        $bar->setMessage('foobar');

        $this->assertEquals('foobar', $bar->getMessage());
        $this->assertEquals('foobar', $bar->message);
    }
}

class Foo
{
    public $message;

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
}

/**
 * @mixin Foo
 */
class Bar
{
    use \Owl\Traits\Decorator;

    public function __construct()
    {
        $this->reference = new Foo();
    }
}
