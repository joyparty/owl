<?php

declare(strict_types=1);

namespace Tests\MVC\Mvc;

use Owl\Mvc\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    public function test()
    {
        $view = new View(TEST_DIR . '/MVC/fixture/view');

        $output = $view->render('page');
        $output = trim($output, "\n");

        $this->assertEquals('<html><body>foobar</body></html>', $output);
    }
}
