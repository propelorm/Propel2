<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

class FooArchiveCollection
{
    protected static $instance;

    public static function getArchiveSingleton()
    {
        if (null === self::$instance) {
            self::$instance = new FooArchive();
        }

        return self::$instance;
    }
}
