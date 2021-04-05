<?php

declare(strict_types=1);

namespace Tests\Service\DB;

use Owl\Service\DB\Expr;
use Owl\Service\DB\Select;
use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{
    public function testStandard()
    {
        $select = $this->select('mytable');
        $this->assertEquals('SELECT * FROM "mytable"', (string) $select);

        $select->orderBy(['id' => 'desc']);
        $this->assertEquals('SELECT * FROM "mytable" ORDER BY "id" DESC', (string) $select);

        $select->limit(10);
        $this->assertEquals('SELECT * FROM "mytable" ORDER BY "id" DESC LIMIT 10', (string) $select);

        $select->offset(10);
        $this->assertEquals('SELECT * FROM "mytable" ORDER BY "id" DESC LIMIT 10 OFFSET 10', (string) $select);

        $select->setColumns('id', 'email');
        $this->assertEquals('SELECT "id", "email" FROM "mytable" ORDER BY "id" DESC LIMIT 10 OFFSET 10', (string) $select);

        $select->setColumns(['id', 'email']);
        $this->assertEquals('SELECT "id", "email" FROM "mytable" ORDER BY "id" DESC LIMIT 10 OFFSET 10', (string) $select);

        $select->setColumns(new Expr('count(1)'));
        $this->assertEquals('SELECT count(1) FROM "mytable" ORDER BY "id" DESC LIMIT 10 OFFSET 10', (string) $select);

        $select->limit('a')->offset('b');
        $this->assertEquals('SELECT count(1) FROM "mytable" ORDER BY "id" DESC', (string) $select);
    }

    public function testOrderBy()
    {
        $select = $this->select('mytable');

        $select->orderBy('foo');
        $this->assertEquals('SELECT * FROM "mytable" ORDER BY "foo"', (string) $select);

        $select->orderBy(new Expr('foo desc'));
        $this->assertEquals('SELECT * FROM "mytable" ORDER BY foo desc', (string) $select);

        $select->orderBy(['foo' => 'desc', 'bar' => 'asc']);
        $this->assertEquals('SELECT * FROM "mytable" ORDER BY "foo" DESC, "bar"', (string) $select);

        $select->orderBy('foo', 'bar', new Expr('baz desc'));
        $this->assertEquals('SELECT * FROM "mytable" ORDER BY "foo", "bar", baz desc', (string) $select);
    }

    public function testWhere()
    {
        $select = $this->select('mytable');

        $select->where('name = ?', 'yangyi');
        list($sql, $params) = $select->compile();

        $this->assertEquals('SELECT * FROM "mytable" WHERE (name = ?)', $sql);
        $this->assertEquals(['yangyi'], $params);

        $select->where('email = ? and active = 1', 'yangyi.cn.gz@gmail.com');
        list($sql, $params) = $select->compile();

        $this->assertEquals('SELECT * FROM "mytable" WHERE (name = ?) AND (email = ? and active = 1)', $sql);
        $this->assertEquals(['yangyi', 'yangyi.cn.gz@gmail.com'], $params);

        $other_select = $this->select('other_table')->setColumns('user_id')->where('other = ?', 'other');
        $select->whereIn('id', $other_select);
        list($sql, $params) = $select->compile();

        $this->assertEquals('SELECT * FROM "mytable" WHERE (name = ?) AND (email = ? and active = 1) AND ("id" IN (SELECT "user_id" FROM "other_table" WHERE (other = ?)))', $sql);
        $this->assertEquals(['yangyi', 'yangyi.cn.gz@gmail.com', 'other'], $params);

        //////////////////////////////
        $select = $this->select('mytable');
        $select->where('email = ? and passwd = ?', 'yangyi.cn.gz@gmail.com', 'abc');
        list($sql, $params) = $select->compile();

        $this->assertEquals('SELECT * FROM "mytable" WHERE (email = ? and passwd = ?)', $sql);
        $this->assertEquals(['yangyi.cn.gz@gmail.com', 'abc'], $params);

        //////////////////////////////
        $select = $this->select('mytable');
        $select->where('email = ? and passwd = ?', ['yangyi.cn.gz@gmail.com', 'abc']);
        list($sql, $params) = $select->compile();

        $this->assertEquals('SELECT * FROM "mytable" WHERE (email = ? and passwd = ?)', $sql);
        $this->assertEquals(['yangyi.cn.gz@gmail.com', 'abc'], $params);

        //////////////////////////////
        $select = $this->select('mytable');
        $select->whereIn('id', [1, 2, 3]);
        list($sql, $params) = $select->compile();

        $this->assertEquals('SELECT * FROM "mytable" WHERE ("id" IN (1,2,3))', $sql);

        $select = $this->select('mytable');
        $select->whereIn('id', new Expr('select id from other'));
        list($sql, $params) = $select->compile();

        $this->assertEquals('SELECT * FROM "mytable" WHERE ("id" IN (select id from other))', $sql);
    }

    public function testUpdateWithoutWhere()
    {
        $this->expectException(\LogicException::class);
        $this->select('mytable')->update(['name' => 'yangyi']);
    }

    public function testUpdateWithLimit()
    {
        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->limit(10)->update(['name' => 'yangyi']);
    }

    public function testUpdateWithOffset()
    {
        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->offset(10)->update(['name' => 'yangyi']);
    }

    public function testUpdateWithGroupBy()
    {
        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->groupBy('email')->update(['name' => 'yangyi']);
    }

    public function testDeleteWithoutWhere()
    {
        $this->expectException(\LogicException::class);
        $this->select('mytable')->delete();
    }

    public function testDeleteWithLimit()
    {
        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->limit(10)->delete();
    }

    public function testDeleteWithOffset()
    {
        $this->expectException(\LogicException::class);
        $this->select('mytable')->where('id = 1')->offset(10)->delete();
    }

    public function testDeleteWithGroupBy()
    {
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
