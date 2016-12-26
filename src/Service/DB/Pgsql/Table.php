<?php
namespace Owl\Service\DB\Pgsql;

class Table extends \Owl\Service\DB\Table
{
    protected function listColumns()
    {
        $sql = <<< 'EOF'
with primary_keys as (
    select
        c.column_name
    from
        information_schema.constraint_column_usage c,
        information_schema.table_constraints t
    where
        c.table_schema = ?
        and c.table_name = ?
        and c.table_schema = t.table_schema
        and c.table_name = t.table_name
        and c.constraint_name = t.constraint_name
        and t.constraint_type = 'PRIMARY KEY'
)
select
    c.table_schema,
    c.table_name,
    c.column_name,
    c.column_default,
    c.data_type,
    c.character_maximum_length,
    c.numeric_precision,
    c.numeric_scale,
    c.is_nullable,
    col_description(t.oid, c.ordinal_position) as comment,
    case when
        exists (select 1 from primary_keys where column_name = c.column_name)
        then 1
        else 0
        end as is_primary
from
    information_schema.columns c,
    pg_class t,
    pg_namespace s
where
    c.table_schema = ?
    and c.table_name = ?
    and c.table_schema = s.nspname
    and c.table_name = t.relname
    and t.relnamespace = s.oid
    and t.relkind = 'r'
order by
    c.ordinal_position
EOF;

        $adapter = $this->adapter;
        list($schema, $table) = $adapter->parseTableName($this->table_name);
        $res = $adapter->execute($sql, $schema, $table, $schema, $table);

        $columns = [];
        while ($row = $res->fetch()) {
            $name = $row['column_name'];

            $column = [
                'primary_key' => $row['is_primary'] === 1,
                'type' => $row['data_type'],
                'sql_type' => $row['data_type'],
                'character_max_length' => $row['character_maximum_length'] * 1,
                'numeric_precision' => $row['numeric_precision'] * 1,
                'numeric_scale' => $row['numeric_scale'] * 1,
                'default_value' => $row['column_default'],
                'not_null' => $row['is_nullable'] === 'NO',
                'comment' => (string) $row['comment'],
            ];

            $columns[$name] = $column;
        }

        return $columns;
    }

    protected function listIndexes()
    {
        $adapter = $this->adapter;
        list($scheme, $table) = $adapter->parseTableName($this->table_name);

        $sql = <<< 'EOF'
select
    s.nspname as scheme_name,
    t.relname as table_name,
    i.relname as index_name,
    a.attname as column_name,
    ix.indisprimary as is_primary,
    ix.indisunique as is_unique
from
    pg_namespace s,
    pg_class t,
    pg_class i,
    pg_index ix,
    pg_attribute a
where
    t.oid = ix.indrelid
    and s.oid = t.relnamespace
    and i.oid = ix.indexrelid
    and a.attrelid = t.oid
    and a.attnum = ANY(ix.indkey)
    and t.relkind = 'r'
    and s.nspname = ?
    and t.relname = ?
EOF;

        $indexes = [];
        $res = $adapter->execute($sql, $scheme, $table);
        while ($row = $res->fetch()) {
            $index_name = $row['index_name'];

            if (!isset($indexes[$index_name])) {
                $indexes[$index_name] = [
                    'name' => $index_name,
                    'columns' => [$row['column_name']],
                    'is_primary' => $row['is_primary'],
                    'is_unique' => $row['is_unique'],
                ];
            } else {
                $indexes[$index_name]['columns'][] = $row['column_name'];
            }
        }

        return array_values($indexes);
    }

    protected function listForeignKeys()
    {
        // http://stackoverflow.com/questions/1152260/postgres-sql-to-list-table-foreign-keys
        $sql = <<< 'EOF'
select
    tc.constraint_name,
    tc.table_schema,
    tc.table_name,
    kcu.column_name,
    ccu.table_schema as foreign_schema_name,
    ccu.table_name as foreign_table_name,
    ccu.column_name as foreign_column_name
from
    information_schema.table_constraints as tc
    join information_schema.key_column_usage as kcu
      on tc.constraint_name = kcu.constraint_name
    join information_schema.constraint_column_usage as ccu
      on ccu.constraint_name = tc.constraint_name
where
    constraint_type = 'FOREIGN KEY'
    and tc.table_schema = ?
    and tc.table_name = ?
EOF;

        $adapter = $this->adapter;
        list($schema, $table) = $adapter->parseTableName($this->table_name);
        $res = $adapter->execute($sql, $schema, $table);

        $result = [];
        while ($row = $res->fetch()) {
            $constraint_name = $row['constraint_name'];

            if (!isset($result[$constraint_name])) {
                $result[$constraint_name] = [
                    'name' => $constraint_name,
                    'reference_table' => $row['foreign_schema_name'] . '.' . $row['foreign_table_name'],
                    'reference_columns' => [],
                ];
            }

            $result[$constraint_name]['reference_columns'][$row['column_name']] = $row['foreign_column_name'];
        }

        return array_values($result);
    }
}
