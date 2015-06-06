<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Versionable;

use Propel\Generator\Model\Field;

/**
 * Behavior to add versionable fields and abilities
 *
 * @author François Zaninotto
 */
class VersionableBehaviorObjectBuilderModifier
{
    protected $behavior;
    protected $table;
    protected $builder;
    protected $objectClassName;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getEntity();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getFieldAttribute($name = 'version_field')
    {
        return strtolower($this->behavior->getFieldForParameter($name)->getName());
    }

    protected function getFieldPhpName($name = 'version_field')
    {
        return $this->behavior->getFieldForParameter($name)->getName();
    }

    protected function getVersionQueryClassName()
    {
        return $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($this->behavior->getVersionEntity()));
    }

    protected function getActiveRecordClassName()
    {
        return $this->builder->getObjectClassName();
    }

    protected function setBuilder($builder)
    {
        $this->builder         = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName  = $builder->getQueryClassName();
    }

    /**
     * Get the getter of the field of the behavior
     *
     * @return string The related getter, e.g. 'getVersion'
     */
    protected function getFieldGetter($name = 'version_field')
    {
        return 'get' . $this->getFieldPhpName($name);
    }

    /**
     * Get the setter of the field of the behavior
     *
     * @return string The related setter, e.g. 'setVersion'
     */
    protected function getFieldSetter($name = 'version_field')
    {
        return 'set' . $this->getFieldPhpName($name);
    }

    public function preSave($builder)
    {
        $script = "if (\$this->isVersioningNecessary()) {
    \$this->set{$this->getFieldPhpName()}(\$this->isNew() ? 1 : \$this->getLastVersionNumber(\$con) + 1);";
        if ($this->behavior->getParameter('log_created_at') == 'true') {
            $col = $this->behavior->getEntity()->getField($this->getParameter('version_created_at_field'));
            $script .= "
    if (!\$this->isFieldModified({$this->builder->getFieldConstant($col)})) {
        \$this->{$this->getFieldSetter('version_created_at_field')}(time());
    }";
        }
        $script .= "
    \$createVersion = true; // for postSave hook
}";

        return $script;
    }

    public function postSave($builder)
    {
        return "if (isset(\$createVersion)) {
    \$this->addVersion(\$con);
}";
    }

    public function postDelete($builder)
    {
        $this->builder = $builder;
        if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->get()['generator']['objectModel']['emulateForeignKeyConstraints']) {
            $script = "// emulate delete cascade
{$this->getVersionQueryClassName()}::create()
    ->filterBy{$this->table->getName()}(\$this)
    ->delete(\$con);";

            return $script;
        }
    }

    public function objectAttributes($builder)
    {
        $script = '';

        $this->addEnforceVersionAttribute($script);

        return $script;
    }

    protected function addEnforceVersionAttribute(&$script)
    {
        $script .= "

/**
 * @var bool
 */
protected \$enforceVersion = false;
        ";
    }

    public function objectMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';
        if ('version' !== $this->getParameter('version_field')) {
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

    protected function addVersionSetter(&$script)
    {
        $script .= "
/**
 * Wrap the setter for version value
 *
 * @param   string
 * @return  \$this|" . $this->table->getName() . "
 */
public function setVersion(\$v)
{
    return \$this->" . $this->getFieldSetter() . "(\$v);
}
";
    }

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
    return \$this->" . $this->getFieldGetter() . "();
}
";
    }

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

    protected function addIsVersioningNecessary(&$script)
    {
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Checks whether the current state must be recorded as a version
 *
 * @return  boolean
 */
public function isVersioningNecessary(\$con = null)
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
        $plural = true;
        foreach ($this->behavior->getVersionableReferrers() as $fk) {
            $fkGetter = $this->builder->getRefFKPhpNameAffix($fk, $plural);
            $script .= "
    // to avoid infinite loops, emulate in save
    \$this->alreadyInSave = true;
    foreach (\$this->get{$fkGetter}(null, \$con) as \$relatedObject) {
        if (\$relatedObject->isVersioningNecessary(\$con)) {
            \$this->alreadyInSave = false;

            return true;
        }
    }
    \$this->alreadyInSave = false;
";
        }
        $script .= "

    return false;
}
";
    }

    protected function addAddVersion(&$script)
    {
        $versionEntity       = $this->behavior->getVersionEntity();
        $versionARClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($versionEntity));

        $script .= "
/**
 * Creates a version of the current object and saves it.
 *
 * @param   ConnectionInterface \$con the connection to use
 *
 * @return  {$versionARClassName} A version object
 */
public function addVersion(\$con = null)
{
    \$this->enforceVersion = false;

    \$version = new {$versionARClassName}();";
        foreach ($this->table->getFields() as $col) {
            $script .= "
    \$version->set" . $col->getName() . "(\$this->get" . $col->getName() . "());";
        }
        $script .= "
    \$version->set{$this->table->getName()}(\$this);";
        $plural = false;
        foreach ($this->behavior->getVersionableFks() as $fk) {
            $fkGetter = $this->builder->getFKPhpNameAffix($fk, $plural);
            $fkVersionFieldName = $fk->getLocalFieldName() . '_version';
            $fkVersionFieldPhpName = $versionEntity->getField($fkVersionFieldName)->getName();
            $script .= "
    if ((\$related = \$this->get{$fkGetter}(null, \$con)) && \$related->getVersion()) {
        \$version->set{$fkVersionFieldPhpName}(\$related->getVersion());
    }";
        }
        $plural = true;
        foreach ($this->behavior->getVersionableReferrers() as $fk) {
            $fkGetter = $this->builder->getRefFKPhpNameAffix($fk, $plural);
            $idsField = $this->behavior->getReferrerIdsField($fk);
            $versionsField = $this->behavior->getReferrerVersionsField($fk);
            $script .= "
    if (\$relateds = \$this->get{$fkGetter}(null, \$con)->toKeyValue('{$fk->getForeignField()->getName()}', 'Version')) {
        \$version->set{$idsField->getName()}(array_keys(\$relateds));
        \$version->set{$versionsField->getName()}(array_values(\$relateds));
    }";
        }
            $script .= "
    \$version->save(\$con);

    return \$version;
}
";
    }

    protected function addToVersion(&$script)
    {
        $ARclassName = $this->getActiveRecordClassName();
        $script .= "
/**
 * Sets the properties of the current object to the value they had at a specific version
 *
 * @param   integer \$versionNumber The version number to read
 * @param   ConnectionInterface \$con The connection to use
 *
 * @return  \$this|{$ARclassName} The current object (for fluent API support)
 */
public function toVersion(\$versionNumber, \$con = null)
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

    protected function addPopulateFromVersion(&$script)
    {
        $ARclassName = $this->getActiveRecordClassName();
        $versionEntity = $this->behavior->getVersionEntity();
        $versionFieldName = $versionEntity->getField($this->behavior->getParameter('version_field'))->getName();
        $versionARClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($versionEntity));
        $tablePKs = $this->table->getPrimaryKey();
        $primaryKeyName = $tablePKs[0]->getName();
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
    \$loadedObjects['{$ARclassName}'][\$version->get{$primaryKeyName}()][\$version->get{$versionFieldName}()] = \$this;";

        foreach ($this->table->getFields() as $col) {
            $script .= "
    \$this->set" . $col->getName() . "(\$version->get" . $col->getName() . "());";
        }
        $plural = false;
        foreach ($this->behavior->getVersionableFks() as $fk) {
            $foreignEntity = $fk->getForeignEntity();
            $foreignVersionEntity = $fk->getForeignEntity()->getBehavior($this->behavior->getId())->getVersionEntity();
            $relatedClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($foreignEntity));
            $relatedVersionQueryClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($foreignVersionEntity));
            $fkFieldName = $fk->getLocalFieldName();
            $fkFieldPhpName = $fk->getLocalField()->getName();
            $fkVersionFieldPhpName = $versionEntity->getField($fkFieldName . '_version')->getName();
            $fkPhpname = $this->builder->getFKPhpNameAffix($fk, $plural);
            // FIXME: breaks lazy-loading
            $script .= "
    if (\$fkValue = \$version->get{$fkFieldPhpName}()) {
        if (isset(\$loadedObjects['{$relatedClassName}']) && isset(\$loadedObjects['{$relatedClassName}'][\$fkValue]) && isset(\$loadedObjects['{$relatedClassName}'][\$fkValue][\$version->get{$fkVersionFieldPhpName}()])) {
            \$related = \$loadedObjects['{$relatedClassName}'][\$fkValue][\$version->get{$fkVersionFieldPhpName}()];
        } else {
            \$related = new {$relatedClassName}();
            \$relatedVersion = {$relatedVersionQueryClassName}::create()
                ->filterBy{$fk->getForeignField()->getName()}(\$fkValue)
                ->filterByVersion(\$version->get{$fkVersionFieldPhpName}())
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
            $foreignEntity = $fk->getEntity();
            $foreignBehavior = $foreignEntity->getBehavior($this->behavior->getId());
            $foreignVersionEntity = $foreignBehavior->getVersionEntity();
            $fkFieldIds = $this->behavior->getReferrerIdsField($fk);
            $fkFieldVersions = $this->behavior->getReferrerVersionsField($fk);
            $relatedVersionQueryClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($foreignVersionEntity));
            $relatedVersionEntityMapClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewEntityMapBuilder($foreignVersionEntity));
            $relatedClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($foreignEntity));
            $fkField = $fk->getForeignField();
            $fkVersionField = $foreignVersionEntity->getField($this->behavior->getParameter('version_field'));

            $script .= "
    if (\$fkValues = \$version->get{$fkFieldIds->getName()}()) {
        \$this->clear{$fkPhpNames}();
        \$fkVersions = \$version->get{$fkFieldVersions->getName()}();
        \$query = {$relatedVersionQueryClassName}::create();
        foreach (\$fkValues as \$key => \$value) {
            \$c1 = \$query->getNewCriterion({$this->builder->getFieldConstant($fkField, $relatedVersionEntityMapClassName)}, \$value);
            \$c2 = \$query->getNewCriterion({$this->builder->getFieldConstant($fkVersionField, $relatedVersionEntityMapClassName)}, \$fkVersions[\$key]);
            \$c1->addAnd(\$c2);
            \$query->addOr(\$c1);
        }
        foreach (\$query->find(\$con) as \$relatedVersion) {
            if (isset(\$loadedObjects['{$relatedClassName}']) && isset(\$loadedObjects['{$relatedClassName}'][\$relatedVersion->get{$fkField->getName()}()]) && isset(\$loadedObjects['{$relatedClassName}'][\$relatedVersion->get{$fkField->getName()}()][\$relatedVersion->get{$fkVersionField->getName()}()])) {
                \$related = \$loadedObjects['{$relatedClassName}'][\$relatedVersion->get{$fkField->getName()}()][\$relatedVersion->get{$fkVersionField->getName()}()];
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

    protected function addGetLastVersionNumber(&$script)
    {
        $script .= "
/**
 * Gets the latest persisted version number for the current object
 *
 * @param   ConnectionInterface \$con the connection to use
 *
 * @return  integer
 */
public function getLastVersionNumber(\$con = null)
{
    \$v = {$this->getVersionQueryClassName()}::create()
        ->filterBy{$this->table->getName()}(\$this)
        ->orderBy{$this->getFieldPhpName()}('desc')
        ->findOne(\$con);
    if (!\$v) {
        return 0;
    }

    return \$v->get{$this->getFieldPhpName()}();
}
";
    }

    protected function addIsLastVersion(&$script)
    {
        $script .= "
/**
 * Checks whether the current object is the latest one
 *
 * @param   ConnectionInterface \$con the connection to use
 *
 * @return  Boolean
 */
public function isLastVersion(\$con = null)
{
    return \$this->getLastVersionNumber(\$con) == \$this->getVersion();
}
";
    }

    protected function addGetOneVersion(&$script)
    {
        $versionARClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($this->behavior->getVersionEntity()));
        $script .= "
/**
 * Retrieves a version object for this entity and a version number
 *
 * @param   integer \$versionNumber The version number to read
 * @param   ConnectionInterface \$con the connection to use
 *
 * @return  {$versionARClassName} A version object
 */
public function getOneVersion(\$versionNumber, \$con = null)
{
    return {$this->getVersionQueryClassName()}::create()
        ->filterBy{$this->table->getName()}(\$this)
        ->filterBy{$this->getFieldPhpName()}(\$versionNumber)
        ->findOne(\$con);
}
";
    }

    protected function addGetAllVersions(&$script)
    {
        $versionEntity = $this->behavior->getVersionEntity();
        $versionARClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($versionEntity));
        //this force the use statement for  VersionEntityMap
        $this->builder->getClassNameFromBuilder($this->builder->getNewEntityMapBuilder($versionEntity));
        $versionForeignField = $versionEntity->getField($this->behavior->getParameter('version_field'));
        $fks = $versionEntity->getForeignKeysReferencingEntity($this->table->getName());
        $relCol = $this->builder->getRefFKPhpNameAffix($fks[0], true);
        $script .= "
/**
 * Gets all the versions of this object, in incremental order
 *
 * @param   ConnectionInterface \$con the connection to use
 *
 * @return  ObjectCollection|{$versionARClassName}[] A list of {$versionARClassName} objects
 */
public function getAllVersions(\$con = null)
{
    \$criteria = new Criteria();
    \$criteria->addAscendingOrderByField({$this->builder->getFieldConstant($versionForeignField)});

    return \$this->get{$relCol}(\$criteria, \$con);
}
";
    }

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
 * @param   string    \$keys            Main key used for the result diff (versions|fields).
 * @param   array     \$ignoredFields  The fields to exclude from the diff.
 *
 * @return  array A list of differences
 */
protected function computeDiff(\$fromVersion, \$toVersion, \$keys = 'fields', \$ignoredFields = array())
{
    \$fromVersionNumber = \$fromVersion['{$this->getFieldPhpName()}'];
    \$toVersionNumber = \$toVersion['{$this->getFieldPhpName()}'];
    \$ignoredFields = array_merge(array(
        '{$this->getFieldPhpName()}',";
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
    ), \$ignoredFields);
    \$diff = array();
    foreach (\$fromVersion as \$key => \$value) {
        if (in_array(\$key, \$ignoredFields)) {
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
 * @param   string              \$keys Main key used for the result diff (versions|fields)
 * @param   ConnectionInterface \$con the connection to use
 * @param   array               \$ignoredFields  The fields to exclude from the diff.
 *
 * @return  array A list of differences
 */
public function compareVersion(\$versionNumber, \$keys = 'fields', \$con = null, \$ignoredFields = array())
{
    \$fromVersion = \$this->toArray();
    \$toVersion = \$this->getOneVersion(\$versionNumber, \$con)->toArray();

    return \$this->computeDiff(\$fromVersion, \$toVersion, \$keys, \$ignoredFields);
}
";
    }

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
 * @param   string              \$keys Main key used for the result diff (versions|fields)
 * @param   ConnectionInterface \$con the connection to use
 * @param   array               \$ignoredFields  The fields to exclude from the diff.
 *
 * @return  array A list of differences
 */
public function compareVersions(\$fromVersionNumber, \$toVersionNumber, \$keys = 'fields', \$con = null, \$ignoredFields = array())
{
    \$fromVersion = \$this->getOneVersion(\$fromVersionNumber, \$con)->toArray();
    \$toVersion = \$this->getOneVersion(\$toVersionNumber, \$con)->toArray();

    return \$this->computeDiff(\$fromVersion, \$toVersion, \$keys, \$ignoredFields);
}
";
    }

    protected function addGetLastVersions(&$script)
    {
        $plural = true;
        $versionEntity = $this->behavior->getVersionEntity();
        $versionARClassName = $this->builder->getNewStubObjectBuilder($versionEntity)->getClassName();
        $versionEntityMapClassName = $this->builder->getClassNameFromBuilder($this->builder->getNewEntityMapBuilder($versionEntity));
        $fks = $versionEntity->getForeignKeysReferencingEntity($this->table->getName());
        $relCol = $this->builder->getRefFKPhpNameAffix($fks[0], $plural);
        $versionGetter = 'get'.$relCol;
        $colPrefix = Field::CONSTANT_PREFIX;

        $script .= <<<EOF
/**
 * retrieve the last \$number versions.
 *
 * @param Integer \$number the number of record to return.
 * @return PropelCollection|{$versionARClassName}[] List of {$versionARClassName} objects
 */
public function getLastVersions(\$number = 10, \$criteria = null, \$con = null)
{
    \$criteria = {$this->getVersionQueryClassName()}::create(null, \$criteria);
    \$criteria->addDescendingOrderByField({$versionEntityMapClassName}::{$colPrefix}VERSION);
    \$criteria->limit(\$number);

    return \$this->{$versionGetter}(\$criteria, \$con);
}
EOF;
    }
}
