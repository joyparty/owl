<?php

namespace Owl\Service\DB\Sqlite;

use Owl\Service\DB\Adapter as BaseAdapter;

if (!extension_loaded('pdo_sqlite')) {
    throw new \Exception('Require "pdo_sqlite" extension.');
}

class Adapter extends BaseAdapter
{
    protected $identifier_symbol = '`';

    /**
     * @param ?string $table
     * @param ?string $column
     *
     * @return mixed
     */
    public function lastID($table = null, $column = null)
    {
        return $this->execute('SELECT last_insert_rowid()')->getCol();
    }

    public function getTables(): array
    {
        // @FIXME
        throw new \Exception('Sqlite\Adapter::getTables() not implement');
    }
}
