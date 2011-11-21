<?php

namespace Propel\Tests\Generator\Behavior\Archivable;

class FooArchiveQuery
{
    protected $pk;

    static public function create()
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
