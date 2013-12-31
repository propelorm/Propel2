<?php

require_once __DIR__.'/../autoload.php.dist';

/**
 * fix var_export behavior with floating number precision since PHP 5.4.22
 *
 * @see https://bugs.php.net/bug.php?id=64760
 * @see https://github.com/sebastianbergmann/phpunit/issues/1052
 */
ini_set('precision', 14);
ini_set('serialize_precision', 14);
