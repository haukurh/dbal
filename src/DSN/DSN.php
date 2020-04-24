<?php

namespace Haukurh\DBAL\DSN;


class DSN
{
    public static function mysql(string $database, string $host, ?int $port = null, string $charset = 'UTF8'): DSNInterface
    {
        return new Mysql($database, $host, $port, $charset);
    }

    public static function mysqlSocket(string $database, string $socket, string $charset = 'UTF8'): DSNInterface
    {
        return new MysqlSocket($database, $socket, $charset);
    }
}
