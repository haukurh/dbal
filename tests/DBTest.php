<?php

declare(strict_types=1);

use Haukurh\DBAL\DB;
use Haukurh\DBAL\DSN\DSN;
use Haukurh\DBAL\Exception\DBException;
use Haukurh\DBAL\Exception\DBInvalidArgument;
use Haukurh\DBAL\Exception\DBInvalidNamedParameter;
use PHPUnit\Framework\TestCase;

final class DBTest extends TestCase
{

    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = new DB(DSN::sqliteMemory());
    }

    public static function tearDownAfterClass(): void
    {
        self::$db = null;
    }

    public function testSqlExecute(): void
    {
        $sql = <<<EOL
create table if not exists articles (
  id integer primary key,
  title varchar(255),
  content text
);
EOL;
        $this->assertInstanceOf(\PDOStatement::class, self::$db->execute($sql));
    }

    public function testInsert(): void
    {
        $this->assertNull(self::$db->insert('articles', [
            'title' => 'Article 1',
            'content' => 'Lorem ipsum dolor sit amet',
        ]));

        $this->assertNull(self::$db->insert('articles', [
            'title' => 'Article 2',
            'content' => 'Cras rutrum leo nunc',
        ]));

        $this->assertNull(self::$db->insert('articles', [
            'title' => 'Article 3',
            'content' => 'Donec vel mauris sit amet diam ultricies',
        ]));
    }

    public function testInvalidInsert(): void
    {
        $this->expectException(DBException::class);
        $this->expectExceptionMessage("no such table");
        self::$db->insert('some_table', [
            'column' => 'yeah',
        ]);
    }

    public function testInsertWithInvalidData(): void
    {
        $this->expectException(DBException::class);
        $this->expectExceptionMessage("has no column named");
        self::$db->insert('articles', [
            'some data',
        ]);
    }

    /**
     * @depends testInsert
     */
    public function testFetchAll(): void
    {
        $articles = self::$db->fetchAll('articles');
        $expected = [
            [
                'id' => '1',
                'title' => 'Article 1',
                'content' => 'Lorem ipsum dolor sit amet',
            ],
            [
                'id' => '2',
                'title' => 'Article 2',
                'content' => 'Cras rutrum leo nunc',
            ],
            [
                'id' => '3',
                'title' => 'Article 3',
                'content' => 'Donec vel mauris sit amet diam ultricies',
            ],
        ];

        $expected = array_map(function ($entry) {
            return (object) $entry;
        }, $expected);

        $this->assertEquals($expected, $articles);
    }

    /**
     * @depends testInsert
     */
    public function testFetchAllWithSubQuery(): void
    {
        $articles = self::$db->fetchAll('articles', 'WHERE id > :id', [':id' => 1]);
        $expected = [
            [
                'id' => '2',
                'title' => 'Article 2',
                'content' => 'Cras rutrum leo nunc',
            ],
            [
                'id' => '3',
                'title' => 'Article 3',
                'content' => 'Donec vel mauris sit amet diam ultricies',
            ],
        ];

        $expected = array_map(function ($entry) {
            return (object) $entry;
        }, $expected);

        $this->assertEquals($expected, $articles);
    }

    /**
     * @depends testInsert
     */
    public function testFetchAllWithInvalidSubQuery(): void
    {
        $this->expectException(DBInvalidNamedParameter::class);
        $this->expectExceptionMessage("Given data must have named parameters");
        self::$db->fetchAll('articles', 'WHERE id > :id', [1]);
    }

    /**
     * @depends testInsert
     */
    public function testFetchAllWithSubQueryAndSelectedColumns(): void
    {
        $articles = self::$db->fetchAll('articles', 'WHERE id <= :id', [':id' => 2], ['title']);
        $expected = [
            ['title' => 'Article 1',],
            ['title' => 'Article 2',],
        ];

        $expected = array_map(function ($entry) {
            return (object) $entry;
        }, $expected);

        $this->assertEquals($expected, $articles);
    }

    /**
     * @depends testInsert
     */
    public function testFetch(): void
    {
        $article = self::$db->fetch('articles');
        $this->assertEquals("Article 1", $article->title);

        $article = self::$db->fetch('articles', 'ORDER BY id DESC');
        $this->assertEquals("Article 3", $article->title);
    }

    /**
     * @depends testFetch
     */
    public function testUpdate(): void
    {
        $expected = 'Article 2 - Updated';
        $query = 'WHERE id = :id';
        $parameters = [':id' => 2];
        self::$db->update('articles',
            [ 'title' => $expected ],
            $query,
            $parameters
        );

        $article = self::$db->fetch('articles', $query, $parameters);

        $this->assertEquals($expected, $article->title);
    }

    public function testSetErrorMode(): void
    {
        $this->assertNull(self::$db->setErrorMode(DB::ERRMODE_EXCEPTION));
    }

    public function testInvalidErrorMode(): void
    {
        $this->expectException(DBInvalidArgument::class);
        $this->expectExceptionMessage("Illegal Error mode");
        $this->assertNull(self::$db->setErrorMode(-99));
    }

    /**
     * @depends testFetch
     */
    public function testFetchStyle(): void
    {
        self::$db->setFetchStyle(PDO::FETCH_ASSOC);
        $article = self::$db->fetch('articles', 'WHERE id = :id', [':id' => 1]);
        $expected = [
            'id' => '1',
            'title' => 'Article 1',
            'content' => 'Lorem ipsum dolor sit amet',
        ];

        $this->assertEquals($expected, $article);

        self::$db->setFetchStyle(PDO::FETCH_OBJ);
    }

    /**
     * @depends testFetchAll
     */
    public function testDelete(): void
    {
        self::$db->delete('articles', 'WHERE id = :id', [':id' => 2]);
        $articles = self::$db->fetchAll('articles');
        $this->assertEquals(2, count($articles));
    }

}