<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Versionable;

use Propel\Generator\Model\Column;

/**
 * Behavior to add versionable columns and abilities
 *
 * @author François Zaninotto
 */
class VersionableBehaviorObjectBuilderModifier
{
    /**
     * @var \Propel\Generator\Behavior\Versionable\VersionableBehavior
     */
    protected $behavior;

    /**
     * @var \Propel\Generator\Model\Table
     */
    protected $table;

    /**
     * @var \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $objectClassName;

    /**
     * @var string
     */
    protected $queryClassName;

    /**
     * @param \Propel\Generator\Behavior\Versionable\VersionableBehavior $behavior
     */
    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getColumnAttribute($name = 'version_column')
    {
        return strtolower($this->behavior->getColumnForParameter($name)->getName());
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getColumnPhpName($name = 'version_column')
    {
        return $this->behavior->getColumnForParameter($name)->getPhpName();
    }

    /**
     * @return string
     */
    protected function getVersionQueryClassName()
    {
        return $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($this->behavior->getVersionTable()));
    }

    /**
     * @return string
     */
    protected function getActiveRecordClassName()
    {
        return $this->builder->getObjectClassName();
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return void
     */
    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
    }

    /**
     * Get the getter of the column of the behavior
     *
     * @param string $name
     *
     * @return string The related getter, e.g. 'getVersion'
     */
    protected function getColumnGetter($name = 'version_column')
    {
        return 'get' . $this->getColumnPhpName($name);
    }

    /**
     * Get the setter of the column of the behavior
     *
     * @param string $name
     *
     * @return string The related setter, e.g. 'setVersion'
     */
    protected function getColumnSetter($name = 'version_column')
    {
        return 'set' . $this->getColumnPhpName($name);
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function preSave($builder)
    {
        $script = "if (\$this->isVersioningNecessary()) {
    \$this->set{$this->getColumnPhpName()}(\$this->isNew() ? 1 : \$this->getLastVersionNumber(\$con) + 1);";
        if ($this->behavior->getParameter('log_created_at') == 'true') {
            $col = $this->behavior->getTable()->getColumn($this->getParameter('version_created_at_column'));
            $script .= "
    if (!\$this->isColumnModified({$this->builder->getColumnConstant($col)})) {
        \$this->{$this->getColumnSetter('version_created_at_column')}(time());
    }";
        }
        $script .= "
    \$createVersion = true; // for postSave hook
}";

        return $script;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function postSave($builder)
    {
        return "if (isset(\$createVersion)) {
    \$this->addVersion(\$con);
}";
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string|null
     */
    public function postDelete($builder)
    {
        $this->builder = $builder;
        if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->getBuildProperty('generator.objectModel.emulateForeignKeyConstraints')) {
            $script = "// emulate delete cascade
{$this->getVersionQueryClassName()}::create()
    ->filterBy{$this->table->getPhpName()}(\$this)
    ->delete(\$con);";

            return $script;
        }

        return null;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function objectAttributes($builder)
    {
        $script = '';

        $this->addEnforceVersionAttribute($script);

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addEnforceVersionAttribute(&$script)
    {
        $script .= "

/**
 * @var bool
 */
protected \$enforceVersion = false;
        ";
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function objectMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';
        if ($this->getParameter('version_column') !== 'version') {
            $this->addVersionSetter($script);
            $this->addVersionGetter($script);
        }
        $this->addEnforceVersioning($script);
        $this->addIsVersioningNecessary($script);
        $this->addAddVersion($script);
        $this->addToVersion($script);
        $this->addPopulateFromVersion($script);
        $this->addGetLastVersionNumber($script);
        $this->addIsLastVersion($script);
        $this->addGetOneVersion($script);
        $this->addGetAllVersions($script);
        $this->addCompareVersion($script);
        $this->addCompareVersions($script);
        $this->addComputeDiff($script);
        $this->addGetLastVersions($script);

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addVersionSetter(&$script)
    {
        $script .= "
/**
 * Wrap the setter for version value
 *
 * @param   string
 * @return  \$this|" . $this->table->getPhpName() . "
 */
public function setVersion(\$v)
{
    return \$this->" . $this->getColumnSetter() . "(\$v);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addVersionGetter(&$script)
    {
        $script .= "
/**
 * Wrap the getter for version value
 *
 * @return  string
 */
public function getVersion()
{
    return \$this->" . $this->getColumnGetter() . "();
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addEnforceVersioning(&$script)
    {
        $objectClass = $this->builder->getStubObjectBuilder()->getClassname();
        $script .= "
/**
 * Enforce a new Version of this object upon next save.
 *
 * @return \$this|{$objectClass}
 */
public function enforceVersioning()
{
    \$this->enforceVersion = true;

    return \$this;
}
        ";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addIsVersioningNecessary(&$script)
    {
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Checks whether the current state must be recorded as a version
 *
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 * @return  boolean
 */
public function isVersioningNecessary(ConnectionInterface \$con = null)
{
    if (\$this->alreadyInSave) {
        return false;
    }

    if (\$this->enforceVersion) {
        return true;
    }

    if ({$queryClassName}::isVersioningEnabled() && (\$this->isNew() || \$this->isModified()) || \$this->isDeleted()) {
        return true;
    }";
        $plural = false;
        foreach ($this->behavior->getVersionableFks() as $fk) {
            $fkGetter = $this->builder->getFKPhpNameAffix($fk, $plural);
            $script .= "
    if (null !== (\$object = \$this->get{$fkGetter}(\$con)) && \$object->isVersioningNecessary(\$con)) {
        return true;
    }
";
        }

        foreach ($this->behavior->getVersionableReferrers() as $fk) {
            if ($fk->isLocalPrimaryKey()) {
                $fkGetter = $this->builder->getRefFKPhpNameAffix($fk);
                $script .= "
    if (\$this->single{$fkGetter}) {

        // to avoid infinite loops, emulate in save
        \$this->alreadyInSave = true;

        if (\$this->single{$fkGetter}->isVersioningNecessary(\$con)) {

            \$this->alreadyInSave = false;
            return true;
        }
        \$this->alreadyInSave = false;
    }
";
            } else {
                $fkGetter = $this->builder->getRefFKPhpNameAffix($fk, $plural = true);
                $script .= "
    if (\$this->coll{$fkGetter}) {

        // to avoid infinite loops, emulate in save
        \$this->alreadyInSave = true;

        foreach (\$this->get{$fkGetter}(null, \$con) as \$relatedObject) {

            if (\$relatedObject->isVersioningNecessary(\$con)) {

                \$this->alreadyInSave = false;
                return true;
            }
        }
        \$this->alreadyInSave = false;
    }
";
            }
        }

        $script .= "

    return false;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addAddVersion(&$script)
    {
        $versionTable = $this->behavior->getVersionTable();
        $versionARClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($versionTable));

        $script .= "
/**
 * Creates a version of the current object and saves it.
 *
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 *
 * @return  {$versionARClassName} A version object
 */
public function addVersion(ConnectionInterface \$con = null)
{
    \$this->enforceVersion = false;

    \$version = new {$versionARClassName}();";
        foreach ($this->table->getColumns() as $col) {
            $script .= "
    \$version->set" . $col->getPhpName() . '($this->get' . $col->getPhpName() . '());';
        }
        $script .= "
    \$version->set{$this->table->getPhpName()}(\$this);";
        $plural = false;
        foreach ($this->behavior->getVersionableFks() as $fk) {
            $fkGetter = $this->builder->getFKPhpNameAffix($fk, $plural);
            $fkVersionColumnName = $fk->getLocalColumnName() . '_version';
            $fkVersionColumnPhpName = $versionTable->getColumn($fkVersionColumnName)->getPhpName();
            $script .= "
    if ((\$related = \$this->get{$fkGetter}(null, \$con)) && \$related->getVersion()) {
        \$version->set{$fkVersionColumnPhpName}(\$related->getVersion());
    }";
        }
        $plural = true;
        foreach ($this->behavior->getVersionableReferrers() as $fk) {
            $plural = !$fk->isLocalPrimaryKey();
            $fkGetter = $this->builder->getRefFKPhpNameAffix($fk, $plural);
            $idsColumn = $this->behavior->getReferrerIdsColumn($fk);
            $versionsColumn = $this->behavior->getReferrerVersionsColumn($fk);
            $script .= "
    \$object = \$this->get{$fkGetter}(null, \$con);
            ";
            if (!$fk->isLocalPrimaryKey()) {
                $script .= "

    if (\$object && \$relateds = \$object->toKeyValue('{$fk->getTable()->getFirstPrimaryKeyColumn()->getPhpName()}', 'Version')) {
        \$version->set{$idsColumn->getPhpName()}(array_keys(\$relateds));
        \$version->set{$versionsColumn->getPhpName()}(array_values(\$relateds));
    }
                ";
            } else {
                $script .= "
    if (\$object && \$object->getVersion()) {
      \$version->set{$idsColumn->getPhpName()}(array(\$object->getPrimaryKey()));
      \$version->set{$versionsColumn->getPhpName()}(array(\$object->getVersion()));
    }
                ";
            }
        }
            $script .= "
    \$version->save(\$con);

    return \$version;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addToVersion(&$script)
    {
        $ARclassName = $this->getActiveRecordClassName();
        $script .= "
/**
 * Sets the properties of the current object to the value they had at a specific version
 *
 * @param   integer \$versionNumber The version number to read
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 *
 * @return  \$this|{$ARclassName} The current object (for fluent API support)
 */
public function toVersion(\$versionNumber, ConnectionInterface \$con = null)
{
    \$version = \$this->getOneVersion(\$versionNumber, \$con);
    if (!\$version) {
        throw new PropelException(sprintf('No {$ARclassName} object found with version %d', \$version));
    }
    \$this->populateFromVersion(\$version, \$con);

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addPopulateFromVersion(&$script)
    {
        $ARclassName = $this->getActiveRecordClassName();
        $versionTable = $this->behavior->getVersionTable();
        $versionColumnName = $versionTable->getColumn($this->behavior->getParameter('version_column'))->getPhpName();
        $versionARClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($versionTable));
        $tablePKs = $this->table->getPrimaryKey();
        $primaryKeyName = $tablePKs[0]->getPhpName();
        $script .= "
/**
 * Sets the properties of the current object to the value they had at a specific version
 *
 * @param {$versionARClassName} \$version The version object to use
 * @param ConnectionInterface   \$con the connection to use
 * @param array                 \$loadedObjects objects that been loaded in a chain of populateFromVersion calls on referrer or fk objects.
 *
 * @return \$this|{$ARclassName} The current object (for fluent API support)
 */
public function populateFromVersion(\$version, \$con = null, &\$loadedObjects = array())
{";
        $script .= "
    \$loadedObjects['{$ARclassName}'][\$version->get{$primaryKeyName}()][\$version->get{$versionColumnName}()] = \$this;";

        $columns = $this->table->getColumns();
        foreach ($columns as $col) {
            $script .= "
    \$this->set" . $col->getPhpName() . '($version->get' . $col->getPhpName() . '());';
        }
        $plural = false;
        foreach ($this->behavior->getVersionableFks() as $fk) {
            $foreignTable = $fk->getForeignTable();

            /** @var \Propel\Generator\Behavior\Versionable\VersionableBehavior $behavior */
            $behavior = $fk->getForeignTable()->getBehavior($this->behavior->getId());
            $foreignVersionTable = $behavior->getVersionTable();
            $relatedClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($foreignTable));
            $relatedVersionQueryClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($foreignVersionTable));
            $fkColumnName = $fk->getLocalColumnName();
            $fkColumnPhpName = $fk->getLocalColumn()->getPhpName();
            $fkVersionColumnPhpName = $versionTable->getColumn($fkColumnName . '_version')->getPhpName();
            $fkPhpname = $this->builder->getFKPhpNameAffix($fk, $plural);
            // FIXME: breaks lazy-loading
            $script .= "
    if (\$fkValue = \$version->get{$fkColumnPhpName}()) {
        if (isset(\$loadedObjects['{$relatedClassName}']) && isset(\$loadedObjects['{$relatedClassName}'][\$fkValue]) && isset(\$loadedObjects['{$relatedClassName}'][\$fkValue][\$version->get{$fkVersionColumnPhpName}()])) {
            \$related = \$loadedObjects['{$relatedClassName}'][\$fkValue][\$version->get{$fkVersionColumnPhpName}()];
        } else {
            \$related = new {$relatedClassName}();
            \$relatedVersion = {$relatedVersionQueryClassName}::create()
                ->filterBy{$fk->getForeignColumn()->getPhpName()}(\$fkValue)
                ->filterBy{$col->getPhpName()}(\$version->get{$fkVersionColumnPhpName}())
                ->findOne(\$con);
            \$related->populateFromVersion(\$relatedVersion, \$con, \$loadedObjects);
            \$related->setNew(false);
        }
        \$this->set{$fkPhpname}(\$related);
    }";
        }
        $plural = true;
        foreach ($this->behavior->getVersionableReferrers() as $fk) {
            $fkPhpNames = $this->builder->getRefFKPhpNameAffix($fk, $plural);
            $plural = false;
            $fkPhpName = $this->builder->getRefFKPhpNameAffix($fk, $plural);
            $foreignTable = $fk->getTable();
            /** @var \Propel\Generator\Behavior\Versionable\VersionableBehavior $foreignBehavior */
            $foreignBehavior = $foreignTable->getBehavior($this->behavior->getId());
            $foreignVersionTable = $foreignBehavior->getVersionTable();
            $fkColumnIds = $this->behavior->getReferrerIdsColumn($fk);
            $fkColumnVersions = $this->behavior->getReferrerVersionsColumn($fk);
            $relatedVersionQueryClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($foreignVersionTable));
            $relatedVersionTableMapClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewTableMapBuilder($foreignVersionTable));
            $relatedClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($foreignTable));
            $fkColumn = $foreignTable->getFirstPrimaryKeyColumn();
            $fkVersionColumn = $foreignVersionTable->getColumn($this->behavior->getParameter('version_column'));

            $script .= "
    if (\$fkValues = \$version->get{$fkColumnIds->getPhpName()}()) {
        \$this->clear{$fkPhpNames}();
        \$fkVersions = \$version->get{$fkColumnVersions->getPhpName()}();
        \$query = {$relatedVersionQueryClassName}::create();
        foreach (\$fkValues as \$key => \$value) {
            \$c1 = \$query->getNewCriterion({$this->builder->getColumnConstant($fkColumn, $relatedVersionTableMapClassName)}, \$value);
            \$c2 = \$query->getNewCriterion({$this->builder->getColumnConstant($fkVersionColumn, $relatedVersionTableMapClassName)}, \$fkVersions[\$key]);
            \$c1->addAnd(\$c2);
            \$query->addOr(\$c1);
        }
        foreach (\$query->find(\$con) as \$relatedVersion) {
            if (isset(\$loadedObjects['{$relatedClassName}']) && isset(\$loadedObjects['{$relatedClassName}'][\$relatedVersion->get{$fkColumn->getPhpName()}()]) && isset(\$loadedObjects['{$relatedClassName}'][\$relatedVersion->get{$fkColumn->getPhpName()}()][\$relatedVersion->get{$fkVersionColumn->getPhpName()}()])) {
                \$related = \$loadedObjects['{$relatedClassName}'][\$relatedVersion->get{$fkColumn->getPhpName()}()][\$relatedVersion->get{$fkVersionColumn->getPhpName()}()];
            } else {
                \$related = new {$relatedClassName}();
                \$related->populateFromVersion(\$relatedVersion, \$con, \$loadedObjects);
                \$related->setNew(false);
            }
            \$this->add{$fkPhpName}(\$related);
            \$this->coll{$fkPhpNames}Partial = false;
        }
    }";
        }
        $script .= "

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetLastVersionNumber(&$script)
    {
        $script .= "
/**
 * Gets the latest persisted version number for the current object
 *
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 *
 * @return  integer
 */
public function getLastVersionNumber(ConnectionInterface \$con = null)
{
    \$v = {$this->getVersionQueryClassName()}::create()
        ->filterBy{$this->table->getPhpName()}(\$this)
        ->orderBy{$this->getColumnPhpName()}('desc')
        ->findOne(\$con);
    if (!\$v) {
        return 0;
    }

    return \$v->get{$this->getColumnPhpName()}();
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addIsLastVersion(&$script)
    {
        $script .= "
/**
 * Checks whether the current object is the latest one
 *
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 *
 * @return  Boolean
 */
public function isLastVersion(ConnectionInterface \$con = null)
{
    return \$this->getLastVersionNumber(\$con) == \$this->getVersion();
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetOneVersion(&$script)
    {
        $versionARClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($this->behavior->getVersionTable()));
        $script .= "
/**
 * Retrieves a version object for this entity and a version number
 *
 * @param   integer \$versionNumber The version number to read
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 *
 * @return  {$versionARClassName} A version object
 */
public function getOneVersion(\$versionNumber, ConnectionInterface \$con = null)
{
    return {$this->getVersionQueryClassName()}::create()
        ->filterBy{$this->table->getPhpName()}(\$this)
        ->filterBy{$this->getColumnPhpName()}(\$versionNumber)
        ->findOne(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetAllVersions(&$script)
    {
        $versionTable = $this->behavior->getVersionTable();
        $versionARClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($versionTable));
        //this force the use statement for  VersionTableMap
        $this->builder->getClassNameFromBuilder($this->builder->getNewTableMapBuilder($versionTable));
        $versionForeignColumn = $versionTable->getColumn($this->behavior->getParameter('version_column'));
        $fks = $versionTable->getForeignKeysReferencingTable($this->table->getName());
        $relCol = $this->builder->getRefFKPhpNameAffix($fks[0], true);
        $script .= "
/**
 * Gets all the versions of this object, in incremental order
 *
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 *
 * @return  ObjectCollection|{$versionARClassName}[] A list of {$versionARClassName} objects
 */
public function getAllVersions(ConnectionInterface \$con = null)
{
    \$criteria = new Criteria();
    \$criteria->addAscendingOrderByColumn({$this->builder->getColumnConstant($versionForeignColumn)});

    return \$this->get{$relCol}(\$criteria, \$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addComputeDiff(&$script)
    {
        $script .= "
/**
 * Computes the diff between two versions.
 * <code>
 * print_r(\$book->computeDiff(1, 2));
 * => array(
 *   '1' => array('Title' => 'Book title at version 1'),
 *   '2' => array('Title' => 'Book title at version 2')
 * );
 * </code>
 *
 * @param   array     \$fromVersion     An array representing the original version.
 * @param   array     \$toVersion       An array representing the destination version.
 * @param   string    \$keys            Main key used for the result diff (versions|columns).
 * @param   array     \$ignoredColumns  The columns to exclude from the diff.
 *
 * @return  array A list of differences
 */
protected function computeDiff(\$fromVersion, \$toVersion, \$keys = 'columns', \$ignoredColumns = array())
{
    \$fromVersionNumber = \$fromVersion['{$this->getColumnPhpName()}'];
    \$toVersionNumber = \$toVersion['{$this->getColumnPhpName()}'];
    \$ignoredColumns = array_merge(array(
        '{$this->getColumnPhpName()}',";
        if ($this->behavior->getParameter('log_created_at') == 'true') {
            $script .= "
        'VersionCreatedAt',";
        }
        if ($this->behavior->getParameter('log_created_by') == 'true') {
            $script .= "
        'VersionCreatedBy',";
        }
        if ($this->behavior->getParameter('log_comment') == 'true') {
            $script .= "
        'VersionComment',";
        }
        $script .= "
    ), \$ignoredColumns);
    \$diff = array();
    foreach (\$fromVersion as \$key => \$value) {
        if (in_array(\$key, \$ignoredColumns)) {
            continue;
        }
        if (\$toVersion[\$key] != \$value) {
            switch (\$keys) {
                case 'versions':
                    \$diff[\$fromVersionNumber][\$key] = \$value;
                    \$diff[\$toVersionNumber][\$key] = \$toVersion[\$key];
                    break;
                default:
                    \$diff[\$key] = array(
                        \$fromVersionNumber => \$value,
                        \$toVersionNumber => \$toVersion[\$key],
                    );
                    break;
            }
        }
    }

    return \$diff;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addCompareVersion(&$script)
    {
        $script .= "
/**
 * Compares the current object with another of its version.
 * <code>
 * print_r(\$book->compareVersion(1));
 * => array(
 *   '1' => array('Title' => 'Book title at version 1'),
 *   '2' => array('Title' => 'Book title at version 2')
 * );
 * </code>
 *
 * @param   integer             \$versionNumber
 * @param   string              \$keys Main key used for the result diff (versions|columns)
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 * @param   array               \$ignoredColumns  The columns to exclude from the diff.
 *
 * @return  array A list of differences
 */
public function compareVersion(\$versionNumber, \$keys = 'columns', ConnectionInterface \$con = null, \$ignoredColumns = array())
{
    \$fromVersion = \$this->toArray();
    \$toVersion = \$this->getOneVersion(\$versionNumber, \$con)->toArray();

    return \$this->computeDiff(\$fromVersion, \$toVersion, \$keys, \$ignoredColumns);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addCompareVersions(&$script)
    {
        $script .= "
/**
 * Compares two versions of the current object.
 * <code>
 * print_r(\$book->compareVersions(1, 2));
 * => array(
 *   '1' => array('Title' => 'Book title at version 1'),
 *   '2' => array('Title' => 'Book title at version 2')
 * );
 * </code>
 *
 * @param   integer             \$fromVersionNumber
 * @param   integer             \$toVersionNumber
 * @param   string              \$keys Main key used for the result diff (versions|columns)
 * @param   ConnectionInterface \$con The ConnectionInterface connection to use.
 * @param   array               \$ignoredColumns  The columns to exclude from the diff.
 *
 * @return  array A list of differences
 */
public function compareVersions(\$fromVersionNumber, \$toVersionNumber, \$keys = 'columns', ConnectionInterface \$con = null, \$ignoredColumns = array())
{
    \$fromVersion = \$this->getOneVersion(\$fromVersionNumber, \$con)->toArray();
    \$toVersion = \$this->getOneVersion(\$toVersionNumber, \$con)->toArray();

    return \$this->computeDiff(\$fromVersion, \$toVersion, \$keys, \$ignoredColumns);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetLastVersions(&$script)
    {
        $plural = true;
        $versionTable = $this->behavior->getVersionTable();
        $versionARClassName = $this->builder->getNewStubObjectBuilder($versionTable)->getClassName();
        $versionTableMapClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewTableMapBuilder($versionTable));
        $fks = $versionTable->getForeignKeysReferencingTable($this->table->getName());
        $relCol = $this->builder->getRefFKPhpNameAffix($fks[0], $plural);
        $versionGetter = 'get' . $relCol;
        $colPrefix = Column::CONSTANT_PREFIX;

        $script .= <<<EOF
/**
 * retrieve the last \$number versions.
 *
 * @param  Integer             \$number The number of record to return.
 * @param  Criteria            \$criteria The Criteria object containing modified values.
 * @param  ConnectionInterface \$con The ConnectionInterface connection to use.
 *
 * @return PropelCollection|{$versionARClassName}[] List of {$versionARClassName} objects
 */
public function getLastVersions(\$number = 10, \$criteria = null, ConnectionInterface \$con = null)
{
    \$criteria = {$this->getVersionQueryClassName()}::create(null, \$criteria);
    \$criteria->addDescendingOrderByColumn({$versionTableMapClassName}::{$colPrefix}VERSION);
    \$criteria->limit(\$number);

    return \$this->{$versionGetter}(\$criteria, \$con);
}
EOF;
    }
}
