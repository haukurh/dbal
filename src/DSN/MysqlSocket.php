<?php

namespace Haukurh\DBAL\DSN;


class MysqlSocket implements DSNInterface
{
    protected $database;
    protected $socket;
    protected $charset;

    public function __construct(string $database, string $socket, string $charset = 'UTF8')
    {
        $this->database = $database;
        $this->socket = $socket;
        $this->charset = $charset;
    }

    /**
     * Returns DSN URI
     *
     * @return string
     */
    public function toString(): string
    {
        return "mysql:unix_socket={$this->socket};dbname={$this->database};charset={$this->charset}";
    }

    public function __toString()
    {
        return $this->toString();
    }
}
