<?php

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
