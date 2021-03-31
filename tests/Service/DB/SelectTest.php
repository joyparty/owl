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
        $this->assertEquals((string) $select, 'SELECT * FROM "mytable"');

        $select->orderBy(['id' => 'desc']);
        $this->assertEquals((string) $select, 'SELECT * FROM "mytable" ORDER BY "id" DESC');

        $select->limit(10);
        $this->assertEquals((string) $select, 'SELECT * FROM "mytable" ORDER BY "id" DESC LIMIT 10');

        $select->offset(10);
        $this->assertEquals((string) $select, 'SELECT * FROM "mytable" ORDER BY "id" DESC LIMIT 10 OFFSET 10');

        $select->setColumns('id', 'email');
        $this->assertEquals((string) $select, 'SELECT "id", "email" FROM "mytable" ORDER BY "id" DESC LIMIT 10 OFFSET 10');

        $select->setColumns(['id', 'email']);
        $this->assertEquals((string) $select, 'SELECT "id", "email" FROM "mytable" ORDER BY "id" DESC LIMIT 10 OFFSET 10');

        $select->setColumns(new Expr('count(1)'));
        $this->assertEquals((string) $select, 'SELECT count(1) FROM "mytable" ORDER BY "id" DESC LIMIT 10 OFFSET 10');

        $select->limit('a')->offset('b');
        $this->assertEquals((string) $select, 'SELECT count(1) FROM "mytable" ORDER BY "id" DESC');
    }

    public function testOrderBy()
    {
        $select = $this->select('mytable');

        $select->orderBy('foo');
        $this->assertEquals((string) $select, 'SELECT * FROM "mytable" ORDER BY "foo"');

        $select->orderBy(new Expr('foo desc'));
        $this->assertEquals((string) $select, 'SELECT * FROM "mytable" ORDER BY foo desc');

        $select->orderBy(['foo' => 'desc', 'bar' => 'asc']);
        $this->assertEquals((string) $select, 'SELECT * FROM "mytable" ORDER BY "foo" DESC, "bar"');

        $select->orderBy('foo', 'bar', new Expr('baz desc'));
        $this->assertEquals((string) $select, 'SELECT * FROM "mytable" ORDER BY "foo", "bar", baz desc');
    }

    public function testWhere()
    {
        $select = $this->select('mytable');

        $select->where('name = ?', 'yangyi');
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (name = ?)');
        $this->assertEquals($params, ['yangyi']);

        $select->where('email = ? and active = 1', 'yangyi.cn.gz@gmail.com');
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (name = ?) AND (email = ? and active = 1)');
        $this->assertEquals($params, ['yangyi', 'yangyi.cn.gz@gmail.com']);

        $other_select = $this->select('other_table')->setColumns('user_id')->where('other = ?', 'other');
        $select->whereIn('id', $other_select);
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (name = ?) AND (email = ? and active = 1) AND ("id" IN (SELECT "user_id" FROM "other_table" WHERE (other = ?)))');
        $this->assertEquals($params, ['yangyi', 'yangyi.cn.gz@gmail.com', 'other']);

        //////////////////////////////
        $select = $this->select('mytable');
        $select->where('email = ? and passwd = ?', 'yangyi.cn.gz@gmail.com', 'abc');
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (email = ? and passwd = ?)');
        $this->assertEquals($params, ['yangyi.cn.gz@gmail.com', 'abc']);

        //////////////////////////////
        $select = $this->select('mytable');
        $select->where('email = ? and passwd = ?', ['yangyi.cn.gz@gmail.com', 'abc']);
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (email = ? and passwd = ?)');
        $this->assertEquals($params, ['yangyi.cn.gz@gmail.com', 'abc']);

        //////////////////////////////
        $select = $this->select('mytable');
        $select->whereIn('id', [1, 2, 3]);
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE ("id" IN (1,2,3))');

        $select = $this->select('mytable');
        $select->whereIn('id', new Expr('select id from other'));
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE ("id" IN (select id from other))');
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
