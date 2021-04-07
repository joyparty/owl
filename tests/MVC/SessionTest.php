<?php

declare(strict_types=1);

namespace Tests\MVC;

use Owl\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected function setUp()
    {
        Session::initialize();
    }

    protected function tearDown()
    {
        Session::getInstance()->destroy();
        unset($_SESSION);
    }

    public function testInitialize()
    {
        $this->assertInstanceof(Session::class, $_SESSION);
    }

    public function testIndirectModification()
    {
        $_SESSION['a']['b']['c'] = 1;
        $_SESSION['a']['b']['d'] = 2;

        $this->assertEquals(1, $_SESSION['a']['b']['c']);
        $this->assertEquals(2, $_SESSION['a']['b']['d']);
    }

    public function testSet()
    {
        $_SESSION['a']['b']['c'] = 1;
        $this->assertTrue(isset($_SESSION['a']['b']['c']));

        unset($_SESSION['a']['b']['c']);
        $this->assertFalse(isset($_SESSION['a']['b']['c']));
    }
}
