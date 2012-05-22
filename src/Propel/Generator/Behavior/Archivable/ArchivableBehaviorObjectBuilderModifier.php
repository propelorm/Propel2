<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Archivable;

/**
 * Keeps tracks of an ActiveRecord object, even after deletion
 *
 * @author François Zaninotto
 */
class ArchivableBehaviorObjectBuilderModifier
{
    protected $behavior;
    protected $table;
    protected $builder;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    /**
     * @return string the PHP code to be added to the builder
     */
    public function objectAttributes($builder)
    {
        $script = '';
        if ($this->behavior->isArchiveOnInsert()) {
            $script .= "protected \$archiveOnInsert = true;
";
        }
        if ($this->behavior->isArchiveOnUpdate()) {
            $script .= "protected \$archiveOnUpdate = true;
";
        }
        if ($this->behavior->isArchiveOnDelete()) {
            $script .= "protected \$archiveOnDelete = true;
";
        }

        return $script;
    }

    /**
     * @return string the PHP code to be added to the builder
     */
    public function postInsert($builder)
    {
        if ($this->behavior->isArchiveOnInsert()) {
            return "if (\$this->archiveOnInsert) {
    \$this->archive(\$con);
} else {
    \$this->archiveOnInsert = true;
}";
        }
    }

    /**
     * @return string the PHP code to be added to the builder
     */
    public function postUpdate($builder)
    {
        if ($this->behavior->isArchiveOnUpdate()) {
            return "if (\$this->archiveOnUpdate) {
    \$this->archive(\$con);
} else {
    \$this->archiveOnUpdate = true;
}";
        }
    }

    /**
     * Using preDelete rather than postDelete to allow user to retrieve
     * related records and archive them before cascade deletion.
     *
     * The actual deletion is made by the query object, so the AR class must tell
     * the query class to enable or disable archiveOnDelete.
     *
     * @return string the PHP code to be added to the builder
     */
    public function preDelete($builder)
    {
        if ($this->behavior->isArchiveOnDelete()) {
            return $this->behavior->renderTemplate('objectPreDelete', array(
                'queryClassName' => $builder->getQueryClassName(),
                'isAddHooks'     => $builder->getGeneratorConfig()->getBuildProperty('addHooks'),
            ));
        }
    }

    /**
     * @return string the PHP code to be added to the builder
     */
    public function objectMethods($builder)
    {
        $this->builder = $builder;
        $script = '';
        $script .= $this->addGetArchive($builder);
        $script .= $this->addArchive($builder);
        $script .= $this->addRestoreFromArchive($builder);
        $script .= $this->addPopulateFromArchive($builder);
        if ($this->behavior->isArchiveOnInsert() || $this->behavior->isArchiveOnUpdate()) {
            $script .= $this->addSaveWithoutArchive($builder);
        }
        if ($this->behavior->isArchiveOnDelete()) {
            $script .= $this->addDeleteWithoutArchive($builder);
        }

        return $script;
    }

    /**
     * @return string the PHP code to be added to the builder
     */
    public function addGetArchive($builder)
    {
        return $this->behavior->renderTemplate('objectGetArchive', array(
            'archiveTablePhpName'   => $this->behavior->getArchiveTablePhpName($builder),
            'archiveTableQueryName' => $this->behavior->getArchiveTableQueryName($builder),
        ));
    }

    /**
     * @return string the PHP code to be added to the builder
     */
    public function addArchive($builder)
    {
        return $this->behavior->renderTemplate('objectArchive', array(
            'archiveTablePhpName'   => $this->behavior->getArchiveTablePhpName($builder),
            'archiveTableQueryName' => $this->behavior->getArchiveTableQueryName($builder),
            'archivedAtColumn'      => $this->behavior->getArchivedAtColumn(),
            'hasArchiveClass'       => $this->behavior->hasArchiveClass()
        ));
    }

    /**
     *
     * @return string the PHP code to be added to the builder
     */
    public function addRestoreFromArchive($builder)
    {
        return $this->behavior->renderTemplate('objectRestoreFromArchive', array(
            'objectClassName' => $this->builder->getObjectClassName(),
        ));
    }

    /**
     * Generates a method to populate the current AR object based on an archive object.
     * This method is necessary because the archive's copyInto() may include the archived_at column
     * and therefore cannot be used. Besides, the way autoincremented PKs are handled should be explicit.
     *
     * @return string the PHP code to be added to the builder
     */
    public function addPopulateFromArchive($builder)
    {
        return $this->behavior->renderTemplate('objectPopulateFromArchive', array(
            'archiveTablePhpName' => $this->behavior->getArchiveTablePhpName($builder),
            'usesAutoIncrement'   => $this->table->hasAutoIncrementPrimaryKey(),
            'objectClassName'     => $this->builder->getObjectClassName(),
            'columns'             => $this->table->getColumns(),
        ));
    }

    /**
     * @return string the PHP code to be added to the builder
     */
    public function addSaveWithoutArchive($builder)
    {
        return $this->behavior->renderTemplate('objectSaveWithoutArchive', array(
            'objectClassName'   => $this->builder->getObjectClassName(),
            'isArchiveOnInsert' => $this->behavior->isArchiveOnInsert(),
            'isArchiveOnUpdate' => $this->behavior->isArchiveOnUpdate(),
        ));
    }

    /**
     * @return string the PHP code to be added to the builder
     */
    public function addDeleteWithoutArchive($builder)
    {
        return $this->behavior->renderTemplate('objectDeleteWithoutArchive', array(
            'objectClassName' => $this->builder->getObjectClassName(),
        ));
    }
}
