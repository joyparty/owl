<?php

declare(strict_types=1);

namespace Tests\Service\DB\Postgres;

use Owl\Service\DB\Expr;
use Owl\Service\DB\Pgsql\Adapter;
use Owl\Service\DB\Select;
use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
    const TABLE = 'my_table';
    const TABLE_OTHER = 'other';

    public static function setUpBeforeClass()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            self::markTestSkipped();
        }

        $table = self::TABLE;
        $other = self::TABLE_OTHER;
        $db = self::db();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id varchar(100) constraint my_table_pk primary key,
            name varchar(100)
        );";
        $db->execute($sql);

        $sql = "CREATE TABLE IF NOT EXISTS {$other} (
            id varchar(100) constraint other_pk primary key
        );";
        $db->execute($sql);


        $db->execute("INSERT INTO {$table} (id, name) VALUES ('1', '111'), ('2', '222'), ('3', '333');");
        $db->execute("INSERT INTO {$other} (id) VALUES ('1'), ('2'), ('3');");
    }

    public static function tearDownAfterClass()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            self::markTestSkipped();
        }

        $table = self::TABLE;
        $other = self::TABLE_OTHER;

        $sql = "DROP TABLE IF EXISTS {$table};";
        self::db()->execute($sql);

        $sql = "DROP TABLE IF EXISTS {$other};";
        self::db()->execute($sql);
    }

    public function testWhereIn()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $table = self::TABLE;
        $other = self::TABLE_OTHER;

        $select = $this->select(self::TABLE);
        $select->whereIn('id', [1, 2, 3]);
        $select->get();
        list($sql, $params) = $select->compile();
        $this->assertEquals("SELECT * FROM \"{$table}\" WHERE (\"id\" IN ('1','2','3'))", $sql);

        $select = $this->select(self::TABLE);
        $select->whereIn('id', new Expr("select id from {$other}"));
        $select->get();
        list($sql, $params) = $select->compile();
        $this->assertEquals("SELECT * FROM \"{$table}\" WHERE (\"id\" IN (select id from {$other}))", $sql);
    }

    public function testUpdateWithoutWhere()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select(self::TABLE)->update(['name' => 'yangyi']);
    }

    public function testUpdateWithLimit()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select(self::TABLE)->where('id = 1')->limit(10)->update(['name' => 'yangyi']);
    }

    public function testUpdateWithOffset()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select(self::TABLE)->where('id = 1')->offset(10)->update(['name' => 'yangyi']);
    }

    public function testUpdateWithGroupBy()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select(self::TABLE)->where('id = 1')->groupBy('email')->update(['name' => 'yangyi']);
    }

    public function testDeleteWithoutWhere()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select(self::TABLE)->delete();
    }

    public function testDeleteWithLimit()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select(self::TABLE)->where('id = 1')->limit(10)->delete();
    }

    public function testDeleteWithOffset()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select(self::TABLE)->where('id = 1')->offset(10)->delete();
    }

    public function testDeleteWithGroupBy()
    {
        if (!isset($_ENV['PGSQL_DSN'])) {
            $this->markTestSkipped();
        }

        $this->expectException(\LogicException::class);
        $this->select(self::TABLE)->where('id = 1')->groupBy('email')->delete();
    }

    protected static function db(): Adapter
    {
        return new Adapter([
            'dsn' => $_ENV['PGSQL_DSN'],
            'user' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASSWORD'],
        ]);
    }

    protected static function select($table): Select
    {

        return self::db()->select($table);
    }
}
