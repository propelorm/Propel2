<?php

require_once __DIR__ . '/../autoload.php.dist';

// check if user is root and
if (function_exists("posix_getuid")) {
    if (posix_getuid() === 0) {
        echo('You must run tests suite with an unprivileged user.');
        die(1);
    }
}

define('DS', DIRECTORY_SEPARATOR);
define('FIXTURES', __DIR__ . DS . 'Fixtures' . DS);

echo sprintf("Tests started in temp %s.\n", sys_get_temp_dir());
/**
 * fix var_export behavior with floating number precision since PHP 5.4.22
 *
 * @see https://bugs.php.net/bug.php?id=64760
 * @see https://github.com/sebastianbergmann/phpunit/issues/1052
 */
ini_set('precision', '14');
ini_set('serialize_precision', '14');
setlocale(LC_ALL, 'en_US.utf8'); //fixed issues with hhvm and iconv
