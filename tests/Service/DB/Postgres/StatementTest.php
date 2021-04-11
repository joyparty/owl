<?php

declare(strict_types=1);

namespace Tests\Service\DB\Postgres;

use Owl\Service\DB\Expr;
use Owl\Service\DB\Pgsql\Adapter;
use Owl\Service\DB\Select;
use PHPUnit\Framework\TestCase;

class StatementTest extends TestCase
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
    }

    protected function select($table): Select
    {
        $adapter = new Adapter(['dsn' => '']);

        return $adapter->select($table);
    }
}