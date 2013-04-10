<?php

$loader = include(__DIR__.'/../../../vendor/autoload.php');
//$loader->add('Propel\Tests\Bookstore', __DIR__ . '/build/classes/Propel/Tests/Bookstore');
$loader->add('Propel\Tests', array(
      __DIR__ . '/build/classes'
 ));
$loader->register();

use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Book;

include('generated-conf/config.php');

$book = new Book();
$book->setTitle('Testbook');
$book->save();

$query = Propel\Tests\Bookstore\BookQuery::create();
$rows = $query->find();

var_dump($rows);