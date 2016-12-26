<?php
namespace Owl\Service\DB\Mysql;

class Table extends \Owl\Service\DB\Table
{
    protected function listColumns()
    {
        $adapter = $this->adapter;
        $select = $adapter->select('information_schema.COLUMNS')
                          ->where('TABLE_SCHEMA = database()')
                          ->where('TABLE_NAME = ?', $this->table_name)
                          ->orderBy('ORDINAL_POSITION');

        $columns = [];
        foreach ($select->iterator() as $row) {
            $name = $row['COLUMN_NAME'];

            $column = [
                'primary_key' => $row['COLUMN_KEY'] === 'PRI',
                'type' => $row['DATA_TYPE'],
                'sql_type' => $row['COLUMN_TYPE'],
                'character_max_length' => $row['CHARACTER_MAXIMUM_LENGTH'] * 1,
                'numeric_precision' => $row['NUMERIC_PRECISION'] * 1,
                'numeric_scale' => $row['NUMERIC_SCALE'] * 1,
                'default_value' => $row['COLUMN_DEFAULT'],
                'not_null' => $row['IS_NULLABLE'] === 'NO',
                'comment' => $row['COLUMN_COMMENT'],
                'charset' => $row['CHARACTER_SET_NAME'],
                'collation' => $row['COLLATION_NAME'],
            ];

            $columns[$name] = $column;
        }

        return $columns;
    }

    protected function listIndexes()
    {
        $adapter = $this->adapter;
        $indexes = [];

        $sql = sprintf('show indexes from %s', $adapter->quoteIdentifier($this->table_name));
        $res = $adapter->execute($sql);

        while ($row = $res->fetch()) {
            $name = $row['Key_name'];

            if (!isset($indexes[$name])) {
                $indexes[$name] = [
                    'name' => $name,
                    'columns' => [$row['Column_name']],
                    'is_primary' => $row['Key_name'] === 'PRIMARY',
                    'is_unique' => $row['Non_unique'] == 0,
                ];
            } else {
                $indexes[$name]['columns'][] = $row['Column_name'];
            }
        }

        return array_values($indexes);
    }

    protected function listForeignKeys()
    {
        $adapter = $this->adapter;
        $select = $adapter->select('information_schema.KEY_COLUMN_USAGE')
            ->setColumns(['CONSTRAINT_NAME', 'TABLE_NAME', 'COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'])
            ->where('TABLE_SCHEMA = database()')
            ->where('TABLE_NAME = ?', $this->table_name)
            ->where('REFERENCED_TABLE_NAME is not null');

        $result = [];
        foreach ($select->iterator() as $row) {
            $constraint_name = $row['CONSTRAINT_NAME'];

            if (!isset($result[$constraint_name])) {
                $result[$constraint_name] = [
                    'name' => $constraint_name,
                    'reference_table' => $row['REFERENCED_TABLE_NAME'],
                    'reference_columns' => [],
                ];
            }

            $result[$constraint_name]['reference_columns'][$row['COLUMN_NAME']] = $row['REFERENCED_COLUMN_NAME'];
        }

        return array_values($result);
    }
}
