<?php

namespace Owl\Service\DB;

use InvalidArgumentException;
use Owl\Logger;
use Owl\Service;
use Owl\Service\Exception as ServiceException;
use PDO;
use PDOStatement;
use Throwable;

/**
 * @mixin PDO
 */
abstract class Adapter extends Service
{
    protected $handler;

    protected $identifier_symbol = '`';
    protected $support_savepoint = true;
    protected $savepoints = [];
    protected $in_transaction = false;

    /**
     * @param ?string $table
     * @param ?string $column
     * @return mixed
     */
    abstract public function lastID($table = null, $column = null);

    /**
     * @return string[]
     */
    abstract public function getTables(): array;

    public function __construct(array $config = [])
    {
        if (!isset($config['dsn'])) {
            throw new InvalidArgumentException('Invalid database config, require "dsn" key.');
        }
        parent::__construct($config);
    }

    public function __destruct()
    {
        if ($this->isConnected()) {
            $this->rollbackAll();
        }
    }

    public function __sleep()
    {
        $this->disconnect();
    }

    public function __call(string $method, array $args)
    {
        return $args
        ? call_user_func_array([$this->connect(), $method], $args)
        : $this->connect()->$method();
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->handler instanceof PDO;
    }

    /**
     * @return PDO
     * @throws
     */
    public function connect(): PDO
    {
        if ($this->isConnected()) {
            return $this->handler;
        }

        $dsn = $this->getConfig('dsn');
        $user = $this->getConfig('user') ?: null;
        $password = $this->getConfig('password') ?: null;
        $options = $this->getConfig('options') ?: [];

        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

        try {
            $handler = new PDO($dsn, $user, $password, $options);

            Logger::log('debug', 'database connected', ['dsn' => $dsn]);
        } catch (Throwable $exception) {
            Logger::log('error', 'database connect failed', [
                'error' => $exception->getMessage(),
                'dsn' => $dsn,
            ]);

            throw new ServiceException('Database connect failed!', 0, $exception);
        }

        return $this->handler = $handler;
    }

    public function disconnect(): self
    {
        if ($this->isConnected()) {
            $this->rollbackAll();
            $this->handler = null;

            Logger::log('debug', 'database disconnected', ['dsn' => $this->getConfig('dsn')]);
        }

        return $this;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function begin(): bool
    {
        if ($this->in_transaction) {
            if (!$this->support_savepoint) {
                throw new \Exception(get_class($this) . ' unsupport savepoint');
            }

            $savepoint = $this->quoteIdentifier(uniqid('savepoint_'));
            $this->execute('SAVEPOINT ' . $savepoint);
            $this->savepoints[] = $savepoint;
        } else {
            $this->execute('BEGIN');
            $this->in_transaction = true;
        }

        return true;
    }

    public function commit(): bool
    {
        if ($this->in_transaction) {
            if ($this->savepoints) {
                $savepoint = array_pop($this->savepoints);
                $this->execute('RELEASE SAVEPOINT ' . $savepoint);
            } else {
                $this->execute('COMMIT');
                $this->in_transaction = false;
            }
        }

        return true;
    }

    public function rollback(): bool
    {
        if ($this->in_transaction) {
            if ($this->savepoints) {
                $savepoint = array_pop($this->savepoints);
                $this->execute('ROLLBACK TO SAVEPOINT ' . $savepoint);
            } else {
                $this->execute('ROLLBACK');
                $this->in_transaction = false;
            }
        }

        return true;
    }

    public function rollbackAll()
    {
        $max = 9; // 最多9次，避免死循环
        while ($this->in_transaction && $max-- > 0) {
            $this->rollback();
        }
    }

    public function inTransaction()
    {
        return $this->in_transaction;
    }

    /**
     * @param string|Statement|PDOStatement $sql
     * @param mixed $params
     * @return Statement
     * @throws ServiceException
     */
    public function execute($sql, $params = null): Statement
    {
        $params = $params ?? [];
        if (!is_array($params)) {
            $params = array_slice(func_get_args(), 1);
        }

        Logger::log('debug', 'database execute', [
            'sql' => ($sql instanceof PDOStatement) ? $sql->queryString : (string) $sql,
            'parameters' => $params,
        ]);

        if ($sql instanceof PDOStatement || $sql instanceof Statement) {
            $sth = $sql;
            $sth->execute($params);
        } else if ($params) {
            $sth = $this->connect()->prepare($sql);
            $sth->execute($params);
        } else {
            $sth = $this->connect()->query($sql);
        }

        $sth->setFetchMode(PDO::FETCH_ASSOC);

        return Statement::factory($sth);
    }

    public function prepare(): Statement
    {
        $handler = $this->connect();
        $statement = call_user_func_array([$handler, 'prepare'], func_get_args());

        return Statement::factory($statement);
    }

    public function quote($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->quote($v);
            }

            return $value;
        }

        if ($value instanceof Expr) {
            return $value;
        }

        if (null === $value) {
            return 'NULL';
        }

        return $this->connect()->quote($value);
    }

    /**
     * @param string|array|Expr $identifier
     * @return array|Expr
     */
    public function quoteIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return array_map([$this, 'quoteIdentifier'], $identifier);
        }

        if ($identifier instanceof Expr) {
            return $identifier;
        }

        $symbol = $this->identifier_symbol;
        $identifier = str_replace(['"', "'", ';', $symbol], '', $identifier);

        $result = [];
        foreach (explode('.', $identifier) as $s) {
            $result[] = $symbol . $s . $symbol;
        }

        return new Expr(implode('.', $result));
    }

    public function select($table): Select
    {
        return new Select($this, $table);
    }

    /**
     * @param string $table_name
     *
     * @return Table
     */
    public function getTable($table_name)
    {
        $class = str_replace('Adapter', 'Table', get_class($this));

        return new $class($this, $table_name);
    }

    /**
     * @param string $table_name
     *
     * @return bool
     */
    public function hasTable($table_name): bool
    {
        $table_name = str_replace($this->identifier_symbol, '', $table_name);

        return in_array($table_name, $this->getTables());
    }

    /**
     * @param string $table
     * @param array $row
     * @return int
     * @throws ServiceException
     */
    public function insert($table, array $row)
    {
        $params = [];
        foreach ($row as $value) {
            if (!($value instanceof Expr)) {
                $params[] = $value;
            }
        }

        $sth = $this->prepareInsert($table, $row);

        return $this->execute($sth, $params)->rowCount();
    }

    public function update($table, array $row, $where = null, $params = null): int
    {
        $where_params = (null === $where || null === $params)
        ? []
        : (is_array($params) ? $params : array_slice(func_get_args(), 3));

        $params = [];
        foreach ($row as $value) {
            if (!($value instanceof Expr)) {
                $params[] = $value;
            }
        }

        if ($where_params) {
            $params = array_merge($params, $where_params);
        }

        $sth = $this->prepareUpdate($table, $row, $where);

        return $this->execute($sth, $params)->rowCount();
    }

    public function delete($table, $where = null, $params = null): int
    {
        if ($where === null || $params === null) {
            $params = [];
        }
        if (!is_array($params)) {
            $params = array_slice(func_get_args(), 2);
        }

        $sth = $this->prepareDelete($table, $where);

        return $this->execute($sth, $params)->rowCount();
    }

    /**
     * @param string $table
     * @param array $columns
     * @return Statement
     */
    public function prepareInsert($table, array $columns)
    {
        $values = array_values($columns);

        if ($values === $columns) {
            $values = array_fill(0, count($columns), '?');
        } else {
            $columns = array_keys($columns);

            foreach ($values as $key => $value) {
                if ($value instanceof Expr) {
                    continue;
                }
                $values[$key] = '?';
            }
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(',', $this->quoteIdentifier($columns)),
            implode(',', $values)
        );

        return $this->prepare($sql);
    }

    /**
     * @param string $table
     * @param array $columns
     * @param ?string $where
     * @return Statement
     */
    public function prepareUpdate($table, array $columns, $where = null)
    {
        $only_column = (array_values($columns) === $columns);

        $set = [];
        foreach ($columns as $column => $value) {
            if ($only_column) {
                $set[] = $this->quoteIdentifier($value) . ' = ?';
            } else {
                $value = ($value instanceof Expr) ? $value : '?';
                $set[] = $this->quoteIdentifier($column) . ' = ' . $value;
            }
        }

        $sql = sprintf('UPDATE %s SET %s', $this->quoteIdentifier($table), implode(',', $set));
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        return $this->prepare($sql);
    }

    /**
     * @param string $table
     * @param ?string $where
     * @return Statement
     */
    public function prepareDelete($table, $where = null)
    {
        $table = $this->quoteIdentifier($table);

        $sql = sprintf('DELETE FROM %s', $table);
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        return $this->prepare($sql);
    }

    /**
     * @param string
     *
     * @return array
     *
     * @deprecated
     */
    public function getColumns($table_name): array
    {
        return $this->getTable($table_name)->getColumns();
    }

    /**
     * @param string
     *
     * @return array
     *
     * @deprecated
     */
    public function getIndexes($table_name): array
    {
        return $this->getTable($table_name)->getIndexes();
    }
}
