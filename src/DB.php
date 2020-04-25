<?php

namespace Haukurh\DBAL;


use Haukurh\DBAL\DSN\DSNInterface;
use Haukurh\DBAL\Exception\DBException;
use Haukurh\DBAL\Exception\DBInvalidDataType;
use Haukurh\DBAL\Exception\DBInvalidFetchStyleException;
use Haukurh\DBAL\Exception\DBParameterKeyCollusion;
use PDO;
use PDOException;
use PDOStatement;

class DB
{
    protected $pdo;

    protected $fetchStyle = PDO::FETCH_OBJ;

    /**
     * DB constructor.
     * @param DSNInterface $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @throws DBException
     */
    public function __construct(DSNInterface $dsn, string $username, string $password, array $options = [])
    {
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            $this->pdo = new PDO($dsn->toString(), $username, $password, $options);
        } catch (PDOException $exception) {
            throw new DBException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * Set fetch style
     *
     * @param int $fetchStyle
     * @throws DBInvalidFetchStyleException
     */
    public function setFetchStyle(int $fetchStyle): void
    {
        $validFetchStyles = [
            PDO::FETCH_ASSOC,
            PDO::FETCH_BOTH,
            PDO::FETCH_BOUND,
            PDO::FETCH_LAZY,
            PDO::FETCH_NAMED,
            PDO::FETCH_NUM,
            PDO::FETCH_OBJ,
        ];
        if (!in_array($fetchStyle, $validFetchStyles)) {
            throw new DBInvalidFetchStyleException("Illegal fetch style");
        }
        $this->fetchStyle = $fetchStyle;
    }

    /**
     * Getter for PDO object
     *
     * @return PDO object
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Set foreign key check ON or OFF
     *
     * @param bool $value ON or OFF
     * @throws DBException
     */
    public function setForeignKeyCheck(bool $value)
    {
        $value = (int) $value;
        try {
            $this->pdo->query("SET FOREIGN_KEY_CHECKS={$value};");
        } catch (PDOException $exception) {
            throw new DBException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * Truncate table
     *
     * @param string $table table to truncate
     * @param bool $force set foreign key check to false before truncate, i.e. skip foreign key check
     * @throws DBException
     */
    public function truncate(string $table, bool $force = false): void
    {
        try {
            if ($force) {
                $this->setForeignKeyCheck(false);
                $this->pdo->prepare("TRUNCATE `{$table}`;")->execute();
                $this->setForeignKeyCheck(true);
            } else {
                $this->pdo->prepare("TRUNCATE `{$table}`;")->execute();
            }
        } catch (PDOException $exception) {
            throw new DBException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * Fetches the next row from a result set
     *
     * @param string $table table to fetch from
     * @param string $query sub query
     * @param array $parameters dynamic parameters, which should be referenced in query
     * @param array $columns columns to fetch, all columns fetch if none given
     * @return mixed
     * @throws DBInvalidDataType
     * @throws DBException
     */
    public function fetch(string $table, string $query = '', array $parameters = [], array $columns = [])
    {
        $stm = $this->preFetch($table, $query, $parameters, $columns);
        return $stm->fetch($this->fetchStyle);
    }

    /**
     * Returns an array containing all of the result set rows
     *
     * @param string $table table to fetch from
     * @param string $query sub query
     * @param array $parameters dynamic parameters, which should be referenced in query
     * @param array $columns columns to fetch, all columns fetch if none given
     * @return mixed
     * @throws DBInvalidDataType
     * @throws DBException
     */
    public function fetchAll(string $table, string $query = '', array $parameters = [], array $columns = [])
    {
        $stm = $this->preFetch($table, $query, $parameters, $columns);
        return $stm->fetchAll($this->fetchStyle);
    }

    /**
     * Insert data into a table
     *
     * @param string $table table to insert into
     * @param array $data pretty much self explanatory
     * @throws DBInvalidDataType
     * @throws DBException
     */
    public function insert(string $table, array $data): void
    {
        $keys = array_keys($data);
        $set = implode('`, `', $keys);
        $values = implode(', :', $keys);
        $sql = "INSERT INTO `{$table}` (`{$set}`) VALUES (:{$values});";

        $this->execute($sql, $data);
    }

    /**
     * Delete records from a table
     *
     * @param string $table table to delete from
     * @param string $query sub query
     * @param array $parameters dynamic parameters, which should be referenced in query
     * @throws DBInvalidDataType
     * @throws DBException
     */
    public function delete(string $table, string $query = '', array $parameters = [])
    {
        $sql = "DELETE FROM `{$table}` {$query};";
        $this->execute($sql, $parameters);
    }

    /**
     * @param string $table
     * @param array $data
     * @param string $query
     * @param array $parameters
     * @throws DBParameterKeyCollusion
     * @throws DBInvalidDataType
     * @throws DBException
     */
    public function update(string $table, array $data, string $query = '', array $parameters = [])
    {
        $keys = array_keys($data);

        $fields = '';
        foreach ($keys as $k) {
            $fields .= " {$k}=:{$k},";
            if (in_array(":{$k}", $parameters)) {
                throw new DBParameterKeyCollusion(
                    "Error parameter key collusion, key '{$k}' exists in data and sub query with prepared keys."
                );
            }
        }
        $fields = trim($fields, ' ,');

        $sql = "UPDATE `{$table}` SET {$fields} {$query};";
        $data += $parameters;

        $this->execute($sql, $data);
    }

    /**
     * Execute an prepared SQL query
     *
     * @param string $sql SQL query to execute
     * @param array $parameters prepared data
     * @return PDOStatement executed statement
     * @throws DBInvalidDataType
     * @throws DBException
     */
    public function execute(string $sql, array $parameters = []): PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($sql);

            $params = [];
            foreach ($parameters as $k => $v) {
                $k = ltrim($k, ':');
                $statement->bindParam(":{$k}", $v, $this->getType($k, $v));
                $params[":{$k}"] = $v;
            }

            $statement->execute($params);

            return $statement;
        } catch (PDOException $exception) {
            throw new DBException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * Alias for 'execute' function
     *
     * @param string $sql SQL query to execute
     * @param array $parameters prepared data
     * @return PDOStatement executed statement
     * @throws DBInvalidDataType
     * @throws DBException
     */
    public function query(string $sql, array $parameters = []): PDOStatement
    {
        return $this->execute($sql, $parameters);
    }

    /**
     * @param string $key
     * @param $value
     * @return mixed
     * @throws DBInvalidDataType
     */
    protected function getType(string $key, $value)
    {
        $type = gettype($value);

        $validTypes = [
            'integer' => PDO::PARAM_INT,
            'NULL' => PDO::PARAM_NULL,
            'boolean' => PDO::PARAM_BOOL,
            'string' => PDO::PARAM_STR,
        ];

        if (!in_array($type, $validTypes)) {
            throw new DBInvalidDataType("Illegal data type for key '{$key}', data type given '{$type}'");
        }

        return $validTypes[$type];
    }

    /**
     * @param string $table table to fetch from
     * @param string $query sub query
     * @param array $parameters dynamic parameters, which should be referenced in query
     * @param array $columns columns to fetch
     * @return mixed
     * @throws DBInvalidDataType
     * @throws DBException
     */
    protected function preFetch(string $table, string $query = '', array $parameters = [], array $columns = []): PDOStatement
    {
        if (empty($columns)) {
            $columns = '*';
        } else {
            $columns = '`' . implode('`, `', $columns) . '`';
        }
        $sql = "SELECT {$columns} FROM `{$table}` {$query};";
        return $this->execute($sql, $parameters);
    }
}
