<?php
namespace Owl\Service\DB;

abstract class Table
{
    protected $adapter;
    protected $table_name;
    protected $columns;
    protected $indexes;
    protected $foreign_keys;

    /**
     * @return array
     *
     * [
     *     (string) => [
     *         'primary_key' => (boolean),
     *         'type' => (string),
     *         'sql_type' => (string),
     *         'character_max_length' => (integer),
     *         'numeric_precision' => (integer),
     *         'numeric_scale' => (integer),
     *         'default_value' => (mixed),
     *         'not_null' => (boolean),
     *         'comment' => (string),
     *     ],
     *     ...
     * ]
     */
    abstract protected function listColumns();

    /**
     * @return array
     *
     * [
     *     [
     *         'name' => (string),
     *         'columns' => [(string), ...],
     *         'is_primary' => (boolean),
     *         'is_unique' => (boolean),
     *     ],
     *     ...
     * ]
     */
    abstract protected function listIndexes();

    /**
     * @return array
     *
     * [
     *     [
     *         'name' => (string),
     *         'reference_table' => (string),
     *         'reference_columns' => [
     *             (string) => (string),    // local column => reference column
     *             ...
     *         ],
     *     ],
     * ]
     */
    abstract protected function listForeignKeys();

    public function __construct(Adapter $adapter, $table_name)
    {
        $this->adapter = $adapter;
        $this->table_name = $table_name;
    }

    public function __clone()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->columns = null;
        $this->indexes = null;
        $this->foreign_keys = null;
    }

    /**
     * 获得表名.
     *
     * @return string
     */
    public function getName()
    {
        return $this->table_name;
    }

    /**
     * 获得数据库连接对象
     *
     * @return \Owl\Service\DB\Adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * 获得所有的字段信息.
     *
     * @return array
     */
    public function getColumns()
    {
        if ($this->columns === null) {
            $this->columns = $this->listColumns();
        }

        return $this->columns;
    }

    /**
     * 获得指定字段的信息.
     *
     * @return array
     */
    public function getColumn($column_name)
    {
        $columns = $this->getColumns();

        return isset($columns[$column_name]) ? $columns[$column_name] : [];
    }

    /**
     * 获得所有的索引信息.
     *
     * @return array
     */
    public function getIndexes()
    {
        if ($this->indexes === null) {
            $this->indexes = $this->listIndexes();
        }

        return $this->indexes;
    }

    /**
     * 检查字段是否存在.
     *
     * @param string $column_name
     *
     * @return bool
     */
    public function hasColumn($column_name)
    {
        $columns = $this->getColumns();

        return isset($columns[$column_name]);
    }

    /**
     * 检查索引是否存在.
     *
     * @param string $index_name
     *
     * @return bool
     */
    public function hasIndex($index_name)
    {
        foreach ($this->getIndexes() as $index) {
            if ($index['name'] === $index_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获得外键关联定义.
     *
     * @see Table::listForeignKeys()
     *
     * @return array
     */
    public function getForeignKeys()
    {
        if ($this->foreign_keys === null) {
            $this->foreign_keys = $this->listForeignKeys();
        }

        return $this->foreign_keys;
    }

    /**
     * 获得有关键关联的表，返回Table对象数组.
     *
     * @return array
     */
    public function getReferenceTables()
    {
        $tables = [];

        foreach ($this->getForeignKeys() as $foreign_key) {
            $table_name = $foreign_key['reference_table'];

            $tables[$table_name] = new static($this->adapter, $table_name);
        }

        return array_values($tables);
    }

    /**
     * 获得指定字段上的索引.
     *
     * @param string $column_name
     *
     * @return array
     */
    public function getIndexesOfColumn($column_name)
    {
        $result = [];

        foreach ($this->getIndexes() as $index) {
            if (in_array($column_name, $index['columns'])) {
                $result[] = $index;
            }
        }

        return $result;
    }

    /**
     * 创建查询对象
     *
     * @return \Owl\Service\DB\Select
     */
    public function select()
    {
        return $this->adapter->select($this->table_name);
    }

    /**
     * 插入一条记录，返回插入的行数.
     *
     * @param array $row
     *
     * @return int
     */
    public function insert(array $row)
    {
        return $this->adapter->insert($this->table_name, $row);
    }

    /**
     * 更新记录，允许指定条件
     * 返回被更新的行数.
     *
     * @param array  $row
     * @param string $where
     * @param mixed  $parameters
     *
     * @return int
     */
    public function update(array $row/*, $where = null, $parameters = null*/)
    {
        $args = func_get_args();
        array_unshift($args, $this->table_name);

        return call_user_func_array([$this->adapter, 'update'], $args);
    }

    /**
     * 删除记录，允许指定条件.
     *
     * @param string $where
     * @param mixed  $parameters
     *
     * @return int
     */
    public function delete(/*$where = null, $parameters = null*/)
    {
        $args = func_get_args();
        array_unshift($args, $this->table_name);

        return call_user_func_array([$this->adapter, 'delete'], $args);
    }
}
