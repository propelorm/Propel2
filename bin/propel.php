<?php

if (!class_exists(\Symfony\Component\Console\Application::class)) {
    $autoloadFileCandidates = [
        __DIR__ . '/../../../autoload.php',
        __DIR__ . '/../autoload.php',
        __DIR__ . '/../autoload.php.dist',
    ];
    foreach ($autoloadFileCandidates as $file) {
        if (file_exists($file)) {
            require_once $file;

            break;
        }
    }
}

use Propel\Generator\Application;
use Propel\Runtime\Propel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;

$finder = new Finder();
$finder->files()->name('*.php')->in(__DIR__ . '/../src/Propel/Generator/Command')->depth(0);

$app = new Application('Propel', Propel::VERSION);

$ns = '\\Propel\\Generator\\Command\\';

foreach ($finder as $file) {
    $r = new \ReflectionClass($ns . $file->getBasename('.php'));
    if ($r->isSubclassOf(Command::class) && !$r->isAbstract()) {
        $app->add($r->newInstance());
    }
}

$app->run();
