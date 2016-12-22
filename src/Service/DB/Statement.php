<?php
namespace Owl\Service\DB;

class Statement
{
    use \Owl\Traits\Decorator;

    protected $statement;

    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $this->reference = $statement;
    }

    /**
     * 返回用于执行的sql语句.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->statement->queryString;
    }

    /**
     * 从查询结果提取下一行.
     *
     * @return array
     */
    public function getRow()
    {
        return $this->statement->fetch();
    }

    /**
     * 从下一行行中获取指定列的数据.
     *
     * @param int $col_number 列序号
     *
     * @return mixed
     */
    public function getCol($col_number = 0)
    {
        return $this->statement->fetch(\PDO::FETCH_COLUMN, $col_number);
    }

    /**
     * 获取查询结果内指定列的所有结果.
     *
     * @param int $col_number 列序号
     *
     * @return array
     */
    public function getCols($col_number = 0)
    {
        return $this->statement->fetchAll(\PDO::FETCH_COLUMN, $col_number);
    }

    /**
     * 返回所有的查询结果，允许以指定的字段内容为返回数组的key.
     *
     * @param string $col
     *
     * @return array
     */
    public function getAll($column = null)
    {
        if (!$column) {
            return $this->fetchAll();
        }

        $rowset = [];
        while ($row = $this->fetch()) {
            $rowset[$row[$column]] = $row;
        }

        return $rowset;
    }

    public static function factory($statement): Statement
    {
        if ($statement instanceof self) {
            return $statement;
        }

        if ($statement instanceof \PDOStatement) {
            return new static($statement);
        }

        throw new \InvalidArgumentException('Invalid statement');
    }
}
