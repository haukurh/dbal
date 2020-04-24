<?php

namespace Haukurh\DBAL\DSN;


class Mysql implements DSNInterface
{
    protected $database;
    protected $host;
    protected $port;
    protected $charset;

    public function __construct(string $database, string $host, ?int $port = null, string $charset = 'UTF8')
    {
        $this->database = $database;
        $this->host = $host;
        $this->port = $port;
        $this->charset = $charset;
    }

    /**
     * Returns DSN URI
     *
     * @return string
     */
    public function toString(): string
    {
        $dsn = "mysql:host={$this->host};dbname={$this->database};";

        if (!is_null($this->port)) {
            $dsn .= "port={$this->port}";
        }

        $dsn .= "charset={$this->charset};";

        return rtrim($dsn, ';');
    }

    public function __toString()
    {
        return $this->toString();
    }
}
