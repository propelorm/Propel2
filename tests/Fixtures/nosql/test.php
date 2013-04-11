<?php

$loader = include(__DIR__.'/../../../vendor/autoload.php');
//$loader->add('Propel\Tests\Bookstore', __DIR__ . '/build/classes/Propel/Tests/Bookstore');
$loader->add('Propel\Tests', array(
      __DIR__ . '/generated-classes'
 ));
$loader->register();

include('generated-conf/config.php');

//$book = new Propel\Tests\Bookstore\Book();
//$book->setTitle('Testbook');
//$book->save(); exit;


$query = Propel\Tests\Bookstore\BookQuery::create();
$object = $query->findOne();
$object->setTitle('Test Mod 2');
$object->save();
var_dump($object); exit;

/*$o = new Propel\Tests\Bookstore\BookLite();
$o->setId(1);
$o->setTitle('Test');
$o->save();*/

$query = Propel\Tests\Bookstore\BookLiteQuery::create();
$object = $query->findOne();

var_dump($object);