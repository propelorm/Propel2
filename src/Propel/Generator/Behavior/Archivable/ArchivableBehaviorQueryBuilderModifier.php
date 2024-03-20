<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Archivable;

use Propel\Generator\Builder\Om\AbstractOMBuilder;

/**
 * Keeps tracks of an ActiveRecord object, even after deletion
 *
 * @author François Zaninotto
 */
class ArchivableBehaviorQueryBuilderModifier
{
    /**
     * @var \Propel\Generator\Behavior\Archivable\ArchivableBehavior
     */
    protected $behavior;

    /**
     * @var \Propel\Generator\Model\Table
     */
    protected $table;

    /**
     * @param \Propel\Generator\Behavior\Archivable\ArchivableBehavior $behavior
     */
    public function __construct(ArchivableBehavior $behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getParameter(string $key)
    {
        return $this->behavior->getParameter($key);
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function queryAttributes(AbstractOMBuilder $builder): string
    {
        $script = '';
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
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string|null
     */
    public function preDeleteQuery(AbstractOMBuilder $builder): ?string
    {
        if ($this->behavior->isArchiveOnDelete()) {
            return "
if (\$this->archiveOnDelete) {
    \$this->archive(\$con);
} else {
    \$this->archiveOnDelete = true;
}
";
        }

        return null;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string|null
     */
    public function postUpdateQuery(AbstractOMBuilder $builder): ?string
    {
        if ($this->behavior->isArchiveOnUpdate()) {
            return "
if (\$this->archiveOnUpdate) {
    \$this->archive(\$con);
} else {
    \$this->archiveOnUpdate = true;
}
";
        }

        return null;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string the PHP code to be added to the builder
     */
    public function queryMethods(AbstractOMBuilder $builder): string
    {
        $script = $this->addArchive($builder);
        if ($this->behavior->isArchiveOnUpdate()) {
            $script .= $this->addSetArchiveOnUpdate($builder);
            $script .= $this->addUpdateWithoutArchive($builder);
        }
        if ($this->behavior->isArchiveOnDelete()) {
            $script .= $this->addSetArchiveOnDelete($builder);
            $script .= $this->addDeleteWithoutArchive($builder);
        }

        return $script;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string the PHP code to be added to the builder
     */
    protected function addArchive(AbstractOMBuilder $builder): string
    {
        return $this->behavior->renderTemplate('queryArchive', [
            'archiveTablePhpName' => $this->behavior->getArchiveTablePhpName($builder),
            'modelTableMap' => $builder->getTableMapClass(),
        ]);
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string the PHP code to be added to the builder
     */
    public function addSetArchiveOnUpdate(AbstractOMBuilder $builder): string
    {
        return $this->behavior->renderTemplate('querySetArchiveOnUpdate');
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string the PHP code to be added to the builder
     */
    public function addUpdateWithoutArchive(AbstractOMBuilder $builder): string
    {
        return $this->behavior->renderTemplate('queryUpdateWithoutArchive');
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string the PHP code to be added to the builder
     */
    public function addSetArchiveOnDelete(AbstractOMBuilder $builder): string
    {
        return $this->behavior->renderTemplate('querySetArchiveOnDelete');
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string the PHP code to be added to the builder
     */
    public function addDeleteWithoutArchive(AbstractOMBuilder $builder): string
    {
        return $this->behavior->renderTemplate('queryDeleteWithoutArchive');
    }
}
