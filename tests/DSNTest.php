<?php

declare(strict_types=1);

use Haukurh\DBAL\DSN\DSN;
use Haukurh\DBAL\DSN\Mysql;
use Haukurh\DBAL\DSN\MysqlSocket;
use Haukurh\DBAL\DSN\Sqlite;
use Haukurh\DBAL\DSN\SqliteMemory;
use PHPUnit\Framework\TestCase;

final class DSNTest extends TestCase
{
    public function testMysqlDSN(): void
    {
        $dsn = new Mysql('some_db', 'localhost', 3996);
        $this->assertEquals('mysql:host=localhost;dbname=some_db;port=3996;charset=UTF8', $dsn->toString());

        $dsn = DSN::mysql('mega_db', '127.0.0.1');
        $this->assertEquals('mysql:host=127.0.0.1;dbname=mega_db;charset=UTF8', $dsn->toString());
    }

    public function testMysqlSocketDSN(): void
    {
        $dsn = new MysqlSocket('some_db', '/var/run/mysql.sock');
        $this->assertEquals('mysql:unix_socket=/var/run/mysql.sock;dbname=some_db;charset=UTF8', $dsn->toString());

        $dsn = DSN::mysqlSocket('mega_db', '/var/run/mysql.sock');
        $this->assertEquals('mysql:unix_socket=/var/run/mysql.sock;dbname=mega_db;charset=UTF8', $dsn->toString());
    }

    public function testSqliteDSN(): void
    {
        $dsn = new Sqlite('database.sqlite');
        $this->assertEquals('sqlite:database.sqlite', $dsn->toString());

        $dsn = DSN::sqlite('/var/lib/sqlite/database.sqlite');
        $this->assertEquals('sqlite:/var/lib/sqlite/database.sqlite', $dsn->toString());
    }

    public function testSqliteMemoryDSN(): void
    {
        $dsn = new SqliteMemory();
        $this->assertEquals('sqlite::memory:', $dsn->toString());

        $dsn = DSN::sqliteMemory();
        $this->assertEquals('sqlite::memory:', $dsn->toString());
    }
}