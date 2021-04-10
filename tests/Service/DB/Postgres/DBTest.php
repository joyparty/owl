<?php

declare(strict_types=1);

namespace Tests\Service\DB\Postgres;

use Owl\Service\DB\Select;
use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
    public function testUpdateWithoutWhere()
    {
        if (intval($_ENV['TEST_DB']) === 0) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select('mytable')->update(['name' => 'yangyi']);
    }

    public function testUpdateWithLimit()
    {
        if (intval($_ENV['TEST_DB']) === 0) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->limit(10)->update(['name' => 'yangyi']);
    }

    public function testUpdateWithOffset()
    {
        if (intval($_ENV['TEST_DB']) === 0) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->offset(10)->update(['name' => 'yangyi']);
    }

    public function testUpdateWithGroupBy()
    {
        if (intval($_ENV['TEST_DB']) === 0) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->groupBy('email')->update(['name' => 'yangyi']);
    }

    public function testDeleteWithoutWhere()
    {
        if (intval($_ENV['TEST_DB']) === 0) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select('mytable')->delete();
    }

    public function testDeleteWithLimit()
    {
        if (intval($_ENV['TEST_DB']) === 0) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->limit(10)->delete();
    }

    public function testDeleteWithOffset()
    {
        if (intval($_ENV['TEST_DB']) === 0) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->offset(10)->delete();
    }

    public function testDeleteWithGroupBy()
    {
        if (intval($_ENV['TEST_DB']) === 0) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->groupBy('email')->delete();
    }

    protected function select($table): Select
    {
        $adapter = new \Owl\Service\DB\Pgsql\Adapter([
            'dsn' => $_ENV['DSN'],
        ]);

        return $adapter->select($table);
    }
}
