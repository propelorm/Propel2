<?php

namespace Propel\Tests\Generator\Behavior\Archivable;

class FooArchiveCollection
{
    protected static $instance;

    static public function getArchiveSingleton()
    {
        if (null === self::$instance) {
            self::$instance = new FooArchive();
        }

        return self::$instance;
    }
}
