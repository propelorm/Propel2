<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

class FooArchiveQuery
{
    /**
     * @var mixed
     */
    protected $pk;

    /**
     * @return \Propel\Tests\Generator\Behavior\Archivable\FooArchiveQuery
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @param mixed $pk
     *
     * @return $this
     */
    public function filterByPrimaryKey($pk)
    {
        $this->pk = $pk;

        return $this;
    }

    /**
     * @return \Propel\Tests\Generator\Behavior\Archivable\FooArchive
     */
    public function findOne()
    {
        $archive = FooArchiveCollection::getArchiveSingleton();
        $archive->setId($this->pk);

        return $archive;
    }
}
