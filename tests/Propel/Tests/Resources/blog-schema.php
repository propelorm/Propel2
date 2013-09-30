<?php

use Propel\Generator\Model\Schema;
use Propel\Generator\Platform\MysqlPlatform;

$database = include __DIR__ . DIRECTORY_SEPARATOR . 'blog-database.php';

$schema = new Schema(new MysqlPlatform());
$schema->setName('acme');
$schema->addDatabase($database);

return $schema;
