<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

class FooArchiveQuery
{
    protected $pk;

    public static function create()
    {
        return new self();
    }

    public function filterByPrimaryKey($pk)
    {
        $this->pk = $pk;

        return $this;
    }

    public function findOne()
    {
        $archive = FooArchiveCollection::getArchiveSingleton();
        $archive->setId($this->pk);

        return $archive;
    }
}
