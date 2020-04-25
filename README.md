haukurh/dbal
============

A simple and lightweight database abstraction layer, which offers a API for basic CRUD operations
but can be extended at will to fit every scenario.

Installation
------------

The easiest way to implement `haukurh/dbal` in your project is to require it with composer.

```bash
composer require haukurh/dbal
```

Initialize
-----------

Initialize the database connection.

```php
<?php

require_once 'vendor/autoload.php';

use Haukurh\DBAL\DB;
use Haukurh\DBAL\DSN\DSN;

$db = new DB(
    DSN::mysql(
        'example_db',
        'localhost',
        3306,
        'UTF8'
    ),
    'username',
    'password',
);

```

Basic usage
-----------

Like previously mentioned, the DB class offers some of the most basic CURD operations which are easy to read and write.

```php
<?php

$contents = [
    'title' => 'Lorem ipsum dolor sit',
    'content' => 'Pellentesque rhoncus dui vitae tincidunt pulvinar...'
];

// Insert a record to the database
$db->insert('articles', $contents);

// Fetch all articles in descending order
$articles = $db->fetchAll('articles', 'ORDER BY id DESC');

// Fetch the next row from a result set
$article = $db->fetch('articles');

// Update records
$db->update('articles', [
    'title' => 'Updated title',
], 'WHERE id = :id', [':id' => 3]);

```

### Named parameters

Dynamic parameters of an SQL query can be dangerous if not implemented correctly, the DB class has built in support
for named parameters in prepared statements.
Which can be circumvented but that would not be very smart.

Example on using named parameters:
```php
<?php

$articles = $db->fetchAll('articles', 'WHERE id >= :id and title like :term', [
    ':id' => 10,
    ':term' => '%ipsum%',
]);
```

Example on how **NOT** to implement:

```php
<?php

$id = 10;
$term = '%ipsum%';

$articles = $db->fetchAll('articles', "WHERE id >= {$id} and title like {$term}");

```

