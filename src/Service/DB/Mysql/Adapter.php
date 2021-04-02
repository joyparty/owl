<?php

namespace Owl\Service\DB\Mysql;

use Owl\Service\DB\Adapter as BaseAdapter;
use PDO;

if (!extension_loaded('pdo_mysql')) {
    throw new \Exception('Require "pdo_mysql" extension.');
}

class Adapter extends BaseAdapter
{
    protected $identifier_symbol = '`';

    public function __construct(array $config = [])
    {
        if (isset($config['options'])) {
            $config['options'][PDO::MYSQL_ATTR_FOUND_ROWS] = true;
        } else {
            $config['options'] = [PDO::MYSQL_ATTR_FOUND_ROWS => true];
        }

        parent::__construct($config);
    }

    /**
     * @param ?string $table
     * @param ?string $column
     *
     * @return mixed
     */
    public function lastID($table = null, $column = null)
    {
        return $this->execute('SELECT last_insert_id()')->getCol();
    }

    public function enableBufferedQuery(): self
    {
        $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        return $this;
    }

    public function disableBufferedQuery(): self
    {
        $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTables(): array
    {
        return $this->select('information_schema.TABLES')
                    ->setColumns('TABLE_NAME')
                    ->where('TABLE_SCHEMA = database()')
                    ->execute()
                    ->getCols();
    }
}
