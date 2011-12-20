<?php

if (!class_exists('\Symfony\Component\Console\Application')) {
    if (file_exists($file = __DIR__.'/../autoload.php')) {
        require_once $file;
    } elseif (file_exists($file = __DIR__.'/../autoload.php.dist')) {
        require_once $file;
    }
}

use \Symfony\Component\Console\Application;

$app = new Application('Propel', '2.0 (dev)');
$app->add(new \Propel\Generator\Command\TestPrepare());
$app->add(new \Propel\Generator\Command\SqlBuild());
$app->run();
