<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\MssqlPlatform;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\OraclePlatform;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Platform\SqlsrvPlatform;

/**
 * Generates a PHP5 base Object class for user object model (OM).
 *
 * This class produces the base object class (e.g. BaseMyTable) which contains
 * all the custom-built accessor and setter methods.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class ObjectBuilder extends AbstractObjectBuilder
{

    /**
     * Returns the package for the base object classes.
     *
     * @return string
     */
    public function getPackage()
    {
        return parent::getPackage() . ".Base";
    }

    /**
     * Returns the namespace for the base class.
     *
     * @return string
     * @see Propel\Generator\Builder\Om.AbstractOMBuilder::getNamespace()
     */
    public function getNamespace()
    {
        if ($namespace = parent::getNamespace()) {
            return $namespace . '\\Base';
        }

        return 'Base';
    }

    /**
     * Returns default key type.
     *
     * If not presented in configuration default will be 'TYPE_PHPNAME'
     *
     * @return string
     */
    public function getDefaultKeyType()
    {
        $defaultKeyType = $this->getBuildProperty('defaultKeyType') ? $this->getBuildProperty('defaultKeyType') : 'phpName';

        return "TYPE_".strtoupper($defaultKeyType);
    }

    /**
     * Returns the name of the current class being built.
     *
     * @return string
     */
    public function getUnprefixedClassName()
    {
        return $this->getStubObjectBuilder()->getUnprefixedClassName();
    }

    /**
     * Validates the current table to make sure that it won't result in
     * generated code that will not parse.
     *
     * This method may emit warnings for code which may cause problems
     * and will throw exceptions for errors that will definitely cause
     * problems.
     */
    protected function validateModel()
    {
        parent::validateModel();

        $table = $this->getTable();

        // Check to see whether any generated foreign key names
        // will conflict with column names.

        $colPhpNames = array();
        $fkPhpNames = array();

        foreach ($table->getColumns() as $col) {
            $colPhpNames[] = $col->getPhpName();
        }

        foreach ($table->getForeignKeys() as $fk) {
            $fkPhpNames[] = $this->getFKPhpNameAffix($fk, false);
        }

        $intersect = array_intersect($colPhpNames, $fkPhpNames);
        if (!empty($intersect)) {
            throw new EngineException("One or more of your column names for [" . $table->getName() . "] table conflict with foreign key names (" . implode(", ", $intersect) . ")");
        }

        // Check foreign keys to see if there are any foreign keys that
        // are also matched with an inversed referencing foreign key
        // (this is currently unsupported behavior)
        // see: http://propel.phpdb.org/trac/ticket/549

        foreach ($table->getForeignKeys() as $fk) {
            if ($fk->isMatchedByInverseFK()) {
                throw new EngineException(sprintf('The 1:1 relationship expressed by foreign key %s is defined in both directions; Propel does not currently support this (if you must have both foreign key constraints, consider adding this constraint with a custom SQL file.)', $fk->getName()));
            }
        }
    }

    /**
     * Returns the appropriate formatter (from platform) for a date/time column.
     *
     * @param  Column $column
     * @return string
     */
    protected function getTemporalFormatter(Column $column)
    {
        $fmt = null;
        if ($column->getType() === PropelTypes::DATE) {
            $fmt = $this->getPlatform()->getDateFormatter();
        } elseif ($column->getType() === PropelTypes::TIME) {
            $fmt = $this->getPlatform()->getTimeFormatter();
        } elseif ($column->getType() === PropelTypes::TIMESTAMP) {
            $fmt = $this->getPlatform()->getTimestampFormatter();
        }

        return $fmt;
    }

    /**
     * Returns the type-casted and stringified default value for the specified
     * Column. This only works for scalar default values currently.
     *
     * @param  Column          $column
     * @throws EngineException
     * @return string
     */
    protected function getDefaultValueString(Column $column)
    {
        $defaultValue = var_export(null, true);
        $val = $column->getPhpDefaultValue();
        if (null === $val) {
            return $defaultValue;
        }

        if ($column->isTemporalType()) {
            $fmt = $this->getTemporalFormatter($column);
            try {
                if (!($this->getPlatform() instanceof MysqlPlatform &&
                ($val === '0000-00-00 00:00:00' || $val === '0000-00-00'))) {
                    // while technically this is not a default value of NULL,
                    // this seems to be closest in meaning.
                    $defDt = new \DateTime($val);
                    $defaultValue = var_export($defDt->format($fmt), true);
                }
            } catch (\Exception $exception) {
                // prevent endless loop when timezone is undefined
                date_default_timezone_set('America/Los_Angeles');
                throw new EngineException(sprintf('Unable to parse default temporal value "%s" for column "%s"', $column->getDefaultValueString(), $column->getFullyQualifiedName()), $exception);
            }
        } elseif ($column->isEnumType()) {
            $valueSet = $column->getValueSet();
            if (!in_array($val, $valueSet)) {
                throw new EngineException(sprintf('Default Value "%s" is not among the enumerated values', $val));
            }
            $defaultValue = array_search($val, $valueSet);
        } elseif ($column->isPhpPrimitiveType()) {
            settype($val, $column->getPhpType());
            $defaultValue = var_export($val, true);
        } elseif ($column->isPhpObjectType()) {
            $defaultValue = 'new '.$column->getPhpType().'(' . var_export($val, true) . ')';
        } elseif ($column->isPhpArrayType()) {
            $defaultValue = var_export($val, true);
        } else {
            throw new EngineException("Cannot get default value string for " . $column->getFullyQualifiedName());
        }

        return $defaultValue;
    }

    /**
     * Adds class phpdoc comment and opening of class.
     *
     * @param string &$script
     */
    protected function addClassOpen(&$script)
    {
        $table = $this->getTable();
        $tableName = $table->getName();
        $tableDesc = $table->getDescription();

        if (null !== ($parentClass = $this->getBehaviorContent('parentClass')) ||
            null !== ($parentClass = ClassTools::classname($this->getBaseClass()))) {
            $parentClass = ' extends '.$parentClass;
        }

        if ($this->getBuildProperty('addClassLevelComment')) {
            $script .= "
/**
 * Base class that represents a row from the '$tableName' table.
 *
 * $tableDesc
 *";
            if ($this->getBuildProperty('addTimeStamp')) {
                $now = strftime('%c');
                $script .= "
 * This class was autogenerated by Propel " . $this->getBuildProperty('version') . " on:
 *
 * $now
 *";
            }
            $script .= "
* @package    propel.generator.".$this->getPackage()."
*/";
        }

        $script .= "
abstract class ".$this->getUnqualifiedClassName().$parentClass." implements ActiveRecordInterface ";

        if ($interface = $this->getInterface()) {
            $script .= ", Child" . ClassTools::classname($interface);
            if ($interface !== ClassTools::classname($interface)) {
                $this->declareClass($interface);
            } else {
                $this->declareClassFromBuilder($this->getInterfaceBuilder());
            }
        }

        $script .= "
{";
    }

    /**
     * Specifies the methods that are added as part of the basic OM class.
     * This can be overridden by subclasses that wish to add more methods.
     *
     * @param string &$script
     * @see ObjectBuilder::addClassBody()
     */
    protected function addClassBody(&$script)
    {
        $this->declareClassFromBuilder($this->getStubObjectBuilder());
        $this->declareClassFromBuilder($this->getStubQueryBuilder());
        $this->declareClassFromBuilder($this->getTableMapBuilder());

        $this->declareClasses(
            '\Exception',
            '\PDO',
            '\Propel\Runtime\Exception\PropelException',
            '\Propel\Runtime\Connection\ConnectionInterface',
            '\Propel\Runtime\Collection\Collection',
            '\Propel\Runtime\Collection\ObjectCollection',
            '\Propel\Runtime\Exception\BadMethodCallException',
            '\Propel\Runtime\Exception\PropelException',
            '\Propel\Runtime\ActiveQuery\Criteria',
            '\Propel\Runtime\ActiveQuery\ModelCriteria',
            '\Propel\Runtime\ActiveRecord\ActiveRecordInterface',
            '\Propel\Runtime\Parser\AbstractParser',
            '\Propel\Runtime\Propel',
            '\Propel\Runtime\Map\TableMap'
        );

        $table = $this->getTable();
        if (!$table->isAlias()) {
            $this->addConstants($script);
            $this->addAttributes($script);
        }

        if ($table->hasCrossForeignKeys()) {
            /* @var $refFK ForeignKey */
            foreach ($table->getCrossFks() as $fkList) {
                list($refFK, $crossFK) = $fkList;
                $fkName = $this->getFKPhpNameAffix($crossFK, true);

                if (!$refFK->isLocalPrimaryKey()) {
                    $this->addScheduledForDeletionAttribute($script, $fkName);
                }
            }
        }

        foreach ($table->getReferrers() as $refFK) {
            $fkName = $this->getRefFKPhpNameAffix($refFK, true);

            if (!$refFK->isLocalPrimaryKey()) {
                $this->addScheduledForDeletionAttribute($script, $fkName);
            }
        }

        if ($this->hasDefaultValues()) {
            $this->addApplyDefaultValues($script);
        }
        $this->addConstructor($script);

        $this->addBaseObjectMethods($script);

        $this->addColumnAccessorMethods($script);
        $this->addColumnMutatorMethods($script);

        $this->addHasOnlyDefaultValues($script);

        $this->addHydrate($script);
        $this->addEnsureConsistency($script);

        if (!$table->isReadOnly()) {
            $this->addManipulationMethods($script);
        }

        if ($this->isAddGenericAccessors()) {
            $this->addGetByName($script);
            $this->addGetByPosition($script);
            $this->addToArray($script);
        }

        if ($this->isAddGenericMutators()) {
            $this->addSetByName($script);
            $this->addSetByPosition($script);
            $this->addFromArray($script);
        }

        $this->addBuildCriteria($script);
        $this->addBuildPkeyCriteria($script);
        $this->addGetPrimaryKey($script);
        $this->addSetPrimaryKey($script);
        $this->addIsPrimaryKeyNull($script);

        $this->addCopy($script);

        $this->addFKMethods($script);
        $this->addRefFKMethods($script);
        $this->addCrossFKMethods($script);
        $this->addClear($script);
        $this->addClearAllReferences($script);

        $this->addPrimaryString($script);

        // apply behaviors
        $this->applyBehaviorModifier('objectMethods', $script, "    ");

        if ($this->getGeneratorConfig()->getBuildProperty('addHooks')) {
            $this->addHookMethods($script);
        }

        $this->addMagicCall($script);
    }

    /**
     * Closes class.
     *
     * @param string &$script
     */
    protected function addClassClose(&$script)
    {
        $script .= "
}
";
        $this->applyBehaviorModifier('objectFilter', $script, "");
    }

    /**
     * Adds any constants to the class.
     *
     * @param string &$script
     */
    protected function addConstants(&$script)
    {
        $script .= "
    /**
     * TableMap class name
     */
    const TABLE_MAP = '" . addslashes($this->getTableMapBuilder()->getFullyQualifiedClassName()) . "';
";
    }

    /**
     * Adds class attributes.
     *
     * @param string &$script
     */
    protected function addAttributes(&$script)
    {
        $table = $this->getTable();

        $script .= "
";

        $script .= $this->renderTemplate('baseObjectAttributes');

        if (!$table->isAlias()) {
            $this->addColumnAttributes($script);
        }

        foreach ($table->getForeignKeys() as $fk) {
            $this->addFKAttributes($script, $fk);
        }

        foreach ($table->getReferrers() as $refFK) {
            $this->addRefFKAttributes($script, $refFK);
        }

        // many-to-many relationships
        foreach ($table->getCrossFks() as $fkList) {
                $crossFK = $fkList[1];
                $this->addCrossFKAttributes($script, $crossFK);
        }

        $this->addAlreadyInSaveAttribute($script);

        // apply behaviors
        $this->applyBehaviorModifier('objectAttributes', $script, "    ");
    }

    /**
     * Adds variables that store column values.
     *
     * @param string &$script
     */
    protected function addColumnAttributes(&$script)
    {

        $table = $this->getTable();

        foreach ($table->getColumns() as $col) {
            $this->addColumnAttributeComment($script, $col);
            $this->addColumnAttributeDeclaration($script, $col);
            if ($col->isLazyLoad() ) {
                $this->addColumnAttributeLoaderComment($script, $col);
                $this->addColumnAttributeLoaderDeclaration($script, $col);
            }
            if ($col->getType() == PropelTypes::OBJECT || $col->getType() == PropelTypes::PHP_ARRAY) {
                $this->addColumnAttributeUnserializedComment($script, $col);
                $this->addColumnAttributeUnserializedDeclaration($script, $col);
            }
        }
    }

    /**
     * Adds comment about the attribute (variable) that stores column values.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addColumnAttributeComment(&$script, Column $column)
    {
        $cptype = $column->getPhpType();
        $clo = $column->getLowercasedName();

        $script .= "
    /**
     * The value for the $clo field.";
        if ($column->getDefaultValue()) {
            if ($column->getDefaultValue()->isExpression()) {
                $script .= "
     * Note: this column has a database default value of: (expression) ".$column->getDefaultValue()->getValue();
            } else {
                $script .= "
     * Note: this column has a database default value of: ". $this->getDefaultValueString($column);
            }
        }
        $script .= "
     * @var        $cptype
     */";
    }

    /**
     * Adds the declaration of a column value storage attribute.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addColumnAttributeDeclaration(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $script .= "
    protected \$" . $clo . ";
";
    }

    /**
     * Adds the comment about the attribute keeping track if an attribute value
     * has been loaded.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addColumnAttributeLoaderComment(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $script .= "
    /**
     * Whether the lazy-loaded \$$clo value has been loaded from database.
     * This is necessary to avoid repeated lookups if \$$clo column is NULL in the db.
     * @var boolean
     */";
    }

    /**
     * Adds the declaration of the attribute keeping track of an attribute
     * loaded state.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addColumnAttributeLoaderDeclaration(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $script .= "
    protected \$".$clo."_isLoaded = false;
";
    }

    /**
     * Adds the comment about the serialized attribute.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addColumnAttributeUnserializedComment(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $script .= "
    /**
     * The unserialized \$$clo value - i.e. the persisted object.
     * This is necessary to avoid repeated calls to unserialize() at runtime.
     * @var object
     */";
    }

    /**
     * Adds the declaration of the serialized attribute.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addColumnAttributeUnserializedDeclaration(&$script, Column $column)
    {
        $clo = $column->getLowercasedName() . "_unserialized";
        $script .= "
    protected \$" . $clo . ";
";
    }

    /**
     * Adds the constructor for this object.
     *
     * @param string &$script
     */
    protected function addConstructor(&$script)
    {
        $this->addConstructorComment($script);
        $this->addConstructorOpen($script);
        if ($this->hasDefaultValues()) {
            $this->addConstructorBody($script);
        }
        $this->addConstructorClose($script);
    }

    /**
     * Adds the comment for the constructor
     *
     * @param string &$script
     */
    protected function addConstructorComment(&$script)
    {
        $script .= "
    /**
     * Initializes internal state of ".$this->getQualifiedClassName()." object.";
        if ($this->hasDefaultValues()) {
            $script .= "
     * @see applyDefaults()";
        }
        $script .= "
     */";
    }

    /**
     * Adds the function declaration for the constructor.
     *
     * @param string &$script
     */
    protected function addConstructorOpen(&$script)
    {
        $script .= "
    public function __construct()
    {";
    }

    /**
     * Adds the function body for the constructor.
     *
     * @param string &$script
     */
    protected function addConstructorBody(&$script)
    {
        $script .= "
        \$this->applyDefaultValues();";
    }

    /**
     * Adds the function close for the constructor.
     *
     * @param string &$script
     */
    protected function addConstructorClose(&$script)
    {
        $script .= "
    }
";
    }

    /**
     * Adds the base object functions.
     *
     * @param string &$script
     */
    protected function addBaseObjectMethods(&$script)
    {
        $script .= $this->renderTemplate('baseObjectMethods', array('className' => $this->getUnqualifiedClassName()));
    }

    /**
     * Adds the base object hook functions.
     *
     * @param string &$script
     */
    protected function addHookMethods(&$script)
    {
        $hooks = array();
        foreach (array('pre', 'post') as $hook) {
            foreach (array('Insert', 'Update', 'Save', 'Delete') as $action) {
                $hooks[$hook.$action] = false === strpos($script, "function $hook.$action(");
            }
        }
        $script .= $this->renderTemplate('baseObjectMethodHook', $hooks);
    }

    /**
     * Adds the applyDefaults() method, which is called from the constructor.
     *
     * @param string &$script
     */
    protected function addApplyDefaultValues(&$script)
    {
        $this->addApplyDefaultValuesComment($script);
        $this->addApplyDefaultValuesOpen($script);
        $this->addApplyDefaultValuesBody($script);
        $this->addApplyDefaultValuesClose($script);
    }

    /**
     * Adds the comment for the applyDefaults method.
     *
     * @param string &$script
     */
    protected function addApplyDefaultValuesComment(&$script)
    {
        $script .= "
    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */";
    }

    /**
     * Adds the function declaration for the applyDefaults method.
     *
     * @param string &$script
     */
    protected function addApplyDefaultValuesOpen(&$script)
    {
        $script .= "
    public function applyDefaultValues()
    {";
    }

    /**
     * Adds the function body of the applyDefault method.
     *
     * @param string &$script
     */
    protected function addApplyDefaultValuesBody(&$script)
    {
        $table = $this->getTable();
        // FIXME - Apply support for PHP default expressions here
        // see: http://propel.phpdb.org/trac/ticket/378

        $colsWithDefaults = array();
        foreach ($table->getColumns() as $column) {
            $def = $column->getDefaultValue();
            if ($def !== null && !$def->isExpression()) {
                $colsWithDefaults[] = $column;
            }
        }

        foreach ($colsWithDefaults as $column) {
            $clo = $column->getLowercasedName();
            $defaultValue = $this->getDefaultValueString($column);
            if ($column->isTemporalType()) {
                $dateTimeClass = $this->getBuildProperty('dateTimeClass');
                if (!$dateTimeClass) {
                    $dateTimeClass = '\DateTime';
                }
                $script .= "
        \$this->".$clo." = PropelDateTime::newInstance($defaultValue, null, '$dateTimeClass');";
            } else {
                $script .= "
        \$this->".$clo." = $defaultValue;";
            }
        }
    }

    /**
     * Adds the function close for the applyDefaults method.
     *
     * @param string &$script
     */
    protected function addApplyDefaultValuesClose(&$script)
    {
        $script .= "
    }
";
    }

    /**
     * Adds a date/time/timestamp getter method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addTemporalAccessor(&$script, Column $column)
    {
        $this->addTemporalAccessorComment($script, $column);
        $this->addTemporalAccessorOpen($script, $column);
        $this->addTemporalAccessorBody($script, $column);
        $this->addTemporalAccessorClose($script);
    }

    /**
     * Adds the comment for a temporal accessor.
     *
     * @param string &$script
     * @param Column $column
     */
    public function addTemporalAccessorComment(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();

        $dateTimeClass = $this->getBuildProperty('dateTimeClass');
        if (!$dateTimeClass) {
            $dateTimeClass = '\DateTime';
        }

        $handleMysqlDate = false;
        if ($this->getPlatform() instanceof MysqlPlatform) {
            if ($column->getType() === PropelTypes::TIMESTAMP) {
                $handleMysqlDate = true;
                $mysqlInvalidDateString = '0000-00-00 00:00:00';
            } elseif ($column->getType() === PropelTypes::DATE) {
                $handleMysqlDate = true;
                $mysqlInvalidDateString = '0000-00-00';
            }
            // 00:00:00 is a valid time, so no need to check for that.
        }

        $script .= "
    /**
     * Get the [optionally formatted] temporal [$clo] column value.
     * {$column->getDescription()}
     *
     * @param      string \$format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or $dateTimeClass object (if format is NULL), NULL if column is NULL" .($handleMysqlDate ? ', and 0 if column value is ' . $mysqlInvalidDateString : '')."
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */";
    }

    /**
     * Adds the function declaration for a temporal accessor.
     *
     * @param string &$script
     * @param Column $column
     */
    public function addTemporalAccessorOpen(&$script, Column $column)
    {
        $cfc = $column->getPhpName();

        $defaultfmt = null;
        $visibility = $column->getAccessorVisibility();

        // Default date/time formatter strings are specified in build.properties
        if ($column->getType() === PropelTypes::DATE) {
            $defaultfmt = $this->getBuildProperty('defaultDateFormat');
        } elseif ($column->getType() === PropelTypes::TIME) {
            $defaultfmt = $this->getBuildProperty('defaultTimeFormat');
        } elseif ($column->getType() === PropelTypes::TIMESTAMP) {
            $defaultfmt = $this->getBuildProperty('defaultTimeStampFormat');
        }

        if (empty($defaultfmt)) {
            $defaultfmt = null;
        }

        $script .= "
    ".$visibility." function get$cfc(\$format = ".var_export($defaultfmt, true)."";
        if ($column->isLazyLoad()) {
            $script .= ", \$con = null";
        }
        $script .= ")
    {";
    }

    /**
     * Gets accessor lazy loaded snippets.
     *
     * @param  Column $column
     * @return string
     */
    protected function getAccessorLazyLoadSnippet(Column $column)
    {
        if ($column->isLazyLoad()) {
            $clo = $column->getLowercasedName();
            $defaultValueString = 'null';
            $def = $column->getDefaultValue();
            if ($def !== null && !$def->isExpression()) {
                $defaultValueString = $this->getDefaultValueString($column);
            }

            return "
        if (!\$this->{$clo}_isLoaded && \$this->{$clo} === {$defaultValueString} && !\$this->isNew()) {
            \$this->load{$column->getPhpName()}(\$con);
        }
";
        }

        return '';
    }

    /**
     * Adds the body of the temporal accessor.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addTemporalAccessorBody(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();

        $dateTimeClass = $this->getBuildProperty('dateTimeClass');
        if (!$dateTimeClass) {
            $dateTimeClass = '\DateTime';
        }
        $this->declareClasses($dateTimeClass);
        $defaultfmt = null;

        // Default date/time formatter strings are specified in build.properties
        if ($column->getType() === PropelTypes::DATE) {
            $defaultfmt = $this->getBuildProperty('defaultDateFormat');
        } elseif ($column->getType() === PropelTypes::TIME) {
            $defaultfmt = $this->getBuildProperty('defaultTimeFormat');
        } elseif ($column->getType() === PropelTypes::TIMESTAMP) {
            $defaultfmt = $this->getBuildProperty('defaultTimeStampFormat');
        }

        if (empty($defaultfmt)) {
            $defaultfmt = null;
        }

        if ($column->isLazyLoad()) {
            $script .= $this->getAccessorLazyLoadSnippet($column);
        }

        $script .= "
        if (\$format === null) {
            return \$this->$clo;
        } else {
            return \$this->$clo instanceof \DateTime ? \$this->{$clo}->format(\$format) : null;
        }";
    }

    /**
     * Adds the body of the temporal accessor.
     *
     * @param string &$script
     */
    protected function addTemporalAccessorClose(&$script)
    {
        $script .= "
    }
";
    }

    /**
     * Adds an object getter method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addObjectAccessor(&$script, Column $column)
    {
        $this->addDefaultAccessorComment($script, $column);
        $this->addDefaultAccessorOpen($script, $column);
        $this->addObjectAccessorBody($script, $column);
        $this->addDefaultAccessorClose($script);
    }

    /**
     * Adds the function body for an object accessor method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addObjectAccessorBody(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $cloUnserialized = $clo.'_unserialized';
        if ($column->isLazyLoad()) {
            $script .= $this->getAccessorLazyLoadSnippet($column);
        }

        $script .= "
        if (null == \$this->$cloUnserialized && null !== \$this->$clo) {
            \$this->$cloUnserialized = unserialize(\$this->$clo);
        }

        return \$this->$cloUnserialized;";
    }

    /**
     * Adds an array getter method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addArrayAccessor(&$script, Column $column)
    {
        $this->addDefaultAccessorComment($script, $column);
        $this->addDefaultAccessorOpen($script, $column);
        $this->addArrayAccessorBody($script, $column);
        $this->addDefaultAccessorClose($script);
    }

    /**
     * Adds the function body for an array accessor method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addArrayAccessorBody(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $cloUnserialized = $clo.'_unserialized';
        if ($column->isLazyLoad()) {
            $script .= $this->getAccessorLazyLoadSnippet($column);
        }

        $script .= "
        if (null === \$this->$cloUnserialized) {
            \$this->$cloUnserialized = array();
        }
        if (!\$this->$cloUnserialized && null !== \$this->$clo) {
            \$$cloUnserialized = substr(\$this->$clo, 2, -2);
            \$this->$cloUnserialized = \$$cloUnserialized ? explode(' | ', \$$cloUnserialized) : array();
        }

        return \$this->$cloUnserialized;";
    }

    /**
     * Adds an enum getter method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addEnumAccessor(&$script, Column $column)
    {
        $this->addDefaultAccessorComment($script, $column);
        $this->addDefaultAccessorOpen($script, $column);
        $this->addEnumAccessorBody($script, $column);
        $this->addDefaultAccessorClose($script);
    }

    /**
     * Adds the function body for an enum accessor method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addEnumAccessorBody(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        if ($column->isLazyLoad()) {
            $script .= $this->getAccessorLazyLoadSnippet($column);
        }

        $script .= "
        if (null === \$this->$clo) {
            return null;
        }
        \$valueSet = " . $this->getTableMapClassName() . "::getValueSet(" . $this->getColumnConstant($column) . ");
        if (!isset(\$valueSet[\$this->$clo])) {
            throw new PropelException('Unknown stored enum key: ' . \$this->$clo);
        }

        return \$valueSet[\$this->$clo];";
    }

    /**
     * Adds a tester method for an array column.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addHasArrayElement(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $cfc = $column->getPhpName();
        $visibility = $column->getAccessorVisibility();
        $singularPhpName = rtrim($cfc, 's');
        $script .= "
    /**
     * Test the presence of a value in the [$clo] array column value.
     * @param      mixed \$value
     * ".$column->getDescription();
        if ($column->isLazyLoad()) {
            $script .= "
     * @param      ConnectionInterface An optional ConnectionInterface connection to use for fetching this lazy-loaded column.";
        }
        $script .= "
     * @return boolean
     */
    $visibility function has$singularPhpName(\$value";
        if ($column->isLazyLoad()) {
            $script .= ", ConnectionInterface \$con = null";
        }

        $script .= ")
    {
        return in_array(\$value, \$this->get$cfc(";
        if ($column->isLazyLoad()) {
            $script .= "\$con";
        }

        $script .= "));
    } // has$singularPhpName()
";
    }

    /**
     * Adds a normal (non-temporal) getter method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addDefaultAccessor(&$script, Column $column)
    {
        $this->addDefaultAccessorComment($script, $column);
        $this->addDefaultAccessorOpen($script, $column);
        $this->addDefaultAccessorBody($script, $column);
        $this->addDefaultAccessorClose($script);
    }

    /**
     * Add the comment for a default accessor method (a getter).
     *
     * @param string &$script
     * @param Column $column
     */
    public function addDefaultAccessorComment(&$script, Column $column)
    {
        $clo=$column->getLowercasedName();

        $script .= "
    /**
     * Get the [$clo] column value.
     * ".$column->getDescription();
        if ($column->isLazyLoad()) {
            $script .= "
     * @param      ConnectionInterface An optional ConnectionInterface connection to use for fetching this lazy-loaded column.";
        }
        $script .= "
     * @return   ".$column->getPhpType()."
     */";
    }

    /**
     * Adds the function declaration for a default accessor.
     *
     * @param string &$script
     * @param Column $column
     */
    public function addDefaultAccessorOpen(&$script, Column $column)
    {
        $cfc = $column->getPhpName();
        $visibility = $column->getAccessorVisibility();

        $script .= "
    ".$visibility." function get$cfc(";
        if ($column->isLazyLoad()) {
            $script .= "ConnectionInterface \$con = null";
        }

        $script .= ")
    {";
    }

    /**
     * Adds the function body for a default accessor method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addDefaultAccessorBody(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        if ($column->isLazyLoad()) {
            $script .= $this->getAccessorLazyLoadSnippet($column);
        }

        $script .= "

        return \$this->$clo;";
    }

    /**
     * Adds the function close for a default accessor method.
     *
     * @param string &$script
     */
    protected function addDefaultAccessorClose(&$script)
    {
        $script .= "
    }
";
    }

    /**
     * Adds the lazy loader method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addLazyLoader(&$script, Column $column)
    {
        $this->addLazyLoaderComment($script, $column);
        $this->addLazyLoaderOpen($script, $column);
        $this->addLazyLoaderBody($script, $column);
        $this->addLazyLoaderClose($script);
    }

    /**
     * Adds the comment for the lazy loader method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addLazyLoaderComment(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();

        $script .= "
    /**
     * Load the value for the lazy-loaded [$clo] column.
     *
     * This method performs an additional query to return the value for
     * the [$clo] column, since it is not populated by
     * the hydrate() method.
     *
     * @param      \$con ConnectionInterface (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - any underlying error will be wrapped and re-thrown.
     */";
    }

    /**
     * Adds the function declaration for the lazy loader method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addLazyLoaderOpen(&$script, Column $column)
    {
        $cfc = $column->getPhpName();
        $script .= "
    protected function load$cfc(ConnectionInterface \$con = null)
    {";
    }

    /**
     * Adds the function body for the lazy loader method.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addLazyLoaderBody(&$script, Column $column)
    {
        $platform = $this->getPlatform();
        $clo = $column->getLowercasedName();

        // pdo_sqlsrv driver requires the use of PDOStatement::bindColumn() or a hex string will be returned
        if ($column->getType() === PropelTypes::BLOB && $platform instanceof SqlsrvPlatform) {
            $script .= "
        \$c = \$this->buildPkeyCriteria();
        \$c->addSelectColumn(".$this->getColumnConstant($column).");
        try {
            \$row = array(0 => null);
            \$dataFetcher = ".$this->getQueryClassName()."::create(null, \$c)->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find(\$con);
            if (\$dataFetcher instanceof PDODataFetcher) {
                \$dataFetcher->bindColumn(1, \$row[0], PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            }
            \$row = \$dataFetcher->fetch(PDO::FETCH_BOUND);
            \$dataFetcher->close();";
        } else {
            $script .= "
        \$c = \$this->buildPkeyCriteria();
        \$c->addSelectColumn(".$this->getColumnConstant($column).");
        try {
            \$dataFetcher = ".$this->getQueryClassName()."::create(null, \$c)->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find(\$con);
            \$row = \$dataFetcher->fetch();
            \$dataFetcher->close();";
        }

        $script .= "

        \$firstColumn = \$row ? current(\$row) : null;
";

        if ($column->getType() === PropelTypes::CLOB && $platform instanceof OraclePlatform) {
            // PDO_OCI returns a stream for CLOB objects, while other PDO adapters return a string...
            $script .= "
            if (\$firstColumn) {
                \$this->$clo = stream_get_contents(\$firstColumn);
            }";
        } elseif ($column->isLobType() && !$platform->hasStreamBlobImpl()) {
            $script .= "
            if (\$firstColumn !== null) {
                \$this->$clo = fopen('php://memory', 'r+');
                fwrite(\$this->$clo, \$firstColumn);
                rewind(\$this->$clo);
            } else {
                \$this->$clo = null;
            }";
        } elseif ($column->isPhpPrimitiveType()) {
            $script .= "
            \$this->$clo = (\$firstColumn !== null) ? (".$column->getPhpType().") \$firstColumn : null;";
        } elseif ($column->isPhpObjectType()) {
            $script .= "
            \$this->$clo = (\$firstColumn !== null) ? new ".$column->getPhpType()."(\$firstColumn) : null;";
        } else {
            $script .= "
            \$this->$clo = \$firstColumn;";
        }

        $script .= "
            \$this->".$clo."_isLoaded = true;
        } catch (Exception \$e) {
            throw new PropelException(\"Error loading value for [$clo] column on demand.\", 0, \$e);
        }";
    }

    /**
     * Adds the function close for the lazy loader.
     *
     * @param string &$script
     */
    protected function addLazyLoaderClose(&$script)
    {
        $script .= "
    }";
    }

    /**
     * Adds the open of the mutator (setter) method for a column.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addMutatorOpen(&$script, Column $column)
    {
        $this->addMutatorComment($script, $column);
        $this->addMutatorOpenOpen($script, $column);
        $this->addMutatorOpenBody($script, $column);
    }

    /**
     * Adds the comment for a mutator.
     *
     * @param string &$script
     * @param Column $column
     */
    public function addMutatorComment(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $script .= "
    /**
     * Set the value of [$clo] column.
     * ".$column->getDescription()."
     * @param      ".$column->getPhpType()." \$v new value
     * @return   ".$this->getObjectClassName(true)." The current object (for fluent API support)
     */";
    }

    /**
     * Adds the mutator function declaration.
     *
     * @param string &$script
     * @param Column $column
     */
    public function addMutatorOpenOpen(&$script, Column $column)
    {
        $cfc = $column->getPhpName();
        $visibility = $column->getMutatorVisibility();

        $script .= "
    ".$visibility." function set$cfc(\$v)
    {";
    }

    /**
     * Adds the mutator open body part.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addMutatorOpenBody(&$script, Column $column)
    {
        $clo = $column->getLowercasedName();
        $cfc = $column->getPhpName();
        if ($column->isLazyLoad()) {
            $script .= "
        // explicitly set the is-loaded flag to true for this lazy load col;
        // it doesn't matter if the value is actually set or not (logic below) as
        // any attempt to set the value means that no db lookup should be performed
        // when the get$cfc() method is called.
        \$this->".$clo."_isLoaded = true;
";
        }
    }

    /**
     * Adds the close of the mutator (setter) method for a column.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addMutatorClose(&$script, Column $column)
    {
        $this->addMutatorCloseBody($script, $column);
        $this->addMutatorCloseClose($script, $column);
    }

    /**
     * Adds the body of the close part of a mutator.
     *
     * @param string &$script
     * @param Column $column
     */
    protected function addMutatorCloseBody(&$script, Column $column)
    {
        $table = $this->getTable();

        if ($column->isForeignKey()) {

            foreach ($column->getForeignKeys() as $fk) {

                $tblFK =  $table->getDatabase()->getTable($fk->getForeignTableName());
                $colFK = $tblFK->getColumn($fk->getMappedForeignColumn($column->getName()));

                $varName = $this->getFKVarName($fk);

                $script .= "
        if (\$this->$varName !== null && \$this->".$varName."->get".$colFK->getPhpName()."() !== \$v) {
            \$this->$varName = null;
        }
";
            } // foreach fk
        } /* if col is foreign key */

        foreach ($column->getReferrers() as $refFK) {

            $tblFK = $this->getDatabase()->getTable($refFK->getForeignTableName());

            if ( $tblFK->getName() != $table->getName() ) {

                foreach ($column->getForeignKeys() as $fk) {

                    $tblFK = $table->getDatabase()->getTable($fk->getForeignTableName());
                    $colFK = $tblFK->getColumn($fk->getMappedForeignColumn($column->getName()));

                    if ($refFK->isLocalPrimaryKey()) {
                        $varName = $this->getPKRefFKVarName($refFK);
                        $script .= "
        // update associated ".$tblFK->getPhpName()."
        if (\$this->$varName !== null) {
            \$this->{$varName}->set".$colFK->getPhpName()."(\$v);
        }
";
                    } else {
                        $collName = $this->getRefFKCollVarName($refFK);
                        $script .= "

        // update associated ".$tblFK->getPhpName()."
        if (\$this->$collName !== null) {
            foreach (\$this->$collName as \$referrerObject) {
                    \$referrerObject->set".$colFK->getPhpName()."(\$v);
                }
            }
";
                    } // if (isLocalPrimaryKey
                } // foreach col->getPrimaryKeys()
            } // if tablFk != table
        } // foreach
    }

    /**
     * Adds the close for the mutator close
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     * @see addMutatorClose()
     **/
    protected function addMutatorCloseClose(&$script, Column $col)
    {
        $cfc = $col->getPhpName();
        $script .= "

        return \$this;
    } // set$cfc()
";
    }

    /**
     * Adds a setter for BLOB columns.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     * @see parent::addColumnMutators()
     */
    protected function addLobMutator(&$script, Column $col)
    {
        $this->addMutatorOpen($script, $col);
        $clo = $col->getLowercasedName();
        $script .= "
        // Because BLOB columns are streams in PDO we have to assume that they are
        // always modified when a new value is passed in.  For example, the contents
        // of the stream itself may have changed externally.
        if (!is_resource(\$v) && \$v !== null) {
            \$this->$clo = fopen('php://memory', 'r+');
            fwrite(\$this->$clo, \$v);
            rewind(\$this->$clo);
        } else { // it's already a stream
            \$this->$clo = \$v;
        }
        \$this->modifiedColumns[] = ".$this->getColumnConstant($col).";
";
        $this->addMutatorClose($script, $col);
    } // addLobMutatorSnippet

    /**
     * Adds a setter method for date/time/timestamp columns.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     * @see parent::addColumnMutators()
     */
    protected function addTemporalMutator(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();

        $dateTimeClass = $this->getBuildProperty('dateTimeClass');
        if (!$dateTimeClass) {
            $dateTimeClass = '\DateTime';
        }
        $this->declareClasses($dateTimeClass, '\Propel\Runtime\Util\PropelDateTime');

        $this->addTemporalMutatorComment($script, $col);
        $this->addMutatorOpenOpen($script, $col);
        $this->addMutatorOpenBody($script, $col);

        $fmt = var_export($this->getTemporalFormatter($col), true);

        $script .= "
        \$dt = PropelDateTime::newInstance(\$v, null, '$dateTimeClass');
        if (\$this->$clo !== null || \$dt !== null) {";

        if (($def = $col->getDefaultValue()) !== null && !$def->isExpression()) {
            $defaultValue = $this->getDefaultValueString($col);
            $script .= "
            if ( (\$dt != \$this->{$clo}) // normalized values don't match
                || (\$dt->format($fmt) === $defaultValue) // or the entered value matches the default
                 ) {";
        } else {
            $script .= "
            if (\$dt !== \$this->{$clo}) {";
        }

        $script .= "
                \$this->$clo = \$dt;
                \$this->modifiedColumns[] = ".$this->getColumnConstant($col).";
            }
        } // if either are not null
";
        $this->addMutatorClose($script, $col);
    }

    public function addTemporalMutatorComment(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();

        $script .= "
    /**
     * Sets the value of [$clo] column to a normalized version of the date/time value specified.
     * ".$col->getDescription()."
     * @param      mixed \$v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   ".$this->getObjectClassName(true)." The current object (for fluent API support)
     */";
    }

    /**
     * Adds a setter for Object columns.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     * @see parent::addColumnMutators()
     */
    protected function addObjectMutator(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();
        $cloUnserialized = $clo.'_unserialized';
        $this->addMutatorOpen($script, $col);

        $script .= "
        if (\$this->$cloUnserialized !== \$v) {
            \$this->$cloUnserialized = \$v;
            \$this->$clo = serialize(\$v);
            \$this->modifiedColumns[] = ".$this->getColumnConstant($col).";
        }
";
        $this->addMutatorClose($script, $col);
    }

    /**
     * Adds a setter for Array columns.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     * @see parent::addColumnMutators()
     */
    protected function addArrayMutator(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();
        $cloUnserialized = $clo.'_unserialized';
        $this->addMutatorOpen($script, $col);

        $script .= "
        if (\$this->$cloUnserialized !== \$v) {
            \$this->$cloUnserialized = \$v;
            \$this->$clo = '| ' . implode(' | ', \$v) . ' |';
            \$this->modifiedColumns[] = ".$this->getColumnConstant($col).";
        }
";
        $this->addMutatorClose($script, $col);
    }

    /**
     * Adds a push method for an array column.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     */
    protected function addAddArrayElement(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();
        $cfc = $col->getPhpName();
        $visibility = $col->getAccessorVisibility();
        $singularPhpName = rtrim($cfc, 's');
        $script .= "
    /**
     * Adds a value to the [$clo] array column value.
     * @param      mixed \$value
     * ".$col->getDescription();
        if ($col->isLazyLoad()) {
            $script .= "
     * @param      ConnectionInterface An optional ConnectionInterface connection to use for fetching this lazy-loaded column.";
        }
        $script .= "
     * @return   ".$this->getObjectClassName(true)." The current object (for fluent API support)
     */
    $visibility function add$singularPhpName(\$value";
        if ($col->isLazyLoad()) {
            $script .= ", ConnectionInterface \$con = null";
        }

        $script .= ")
    {
        \$currentArray = \$this->get$cfc(";
        if ($col->isLazyLoad()) {
            $script .= "\$con";
        }

        $script .= ");
        \$currentArray []= \$value;
        \$this->set$cfc(\$currentArray);

        return \$this;
    } // add$singularPhpName()
";
    }

    /**
     * Adds a remove method for an array column.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     */
    protected function addRemoveArrayElement(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();
        $cfc = $col->getPhpName();
        $visibility = $col->getAccessorVisibility();
        $singularPhpName = rtrim($cfc, 's');
        $script .= "
    /**
     * Removes a value from the [$clo] array column value.
     * @param      mixed \$value
     * ".$col->getDescription();
        if ($col->isLazyLoad()) {
            $script .= "
     * @param      ConnectionInterface An optional ConnectionInterface connection to use for fetching this lazy-loaded column.";
        }
        $script .= "
     * @return   ".$this->getObjectClassName(true)." The current object (for fluent API support)
     */
    $visibility function remove$singularPhpName(\$value";
        if ($col->isLazyLoad()) {
            $script .= ", ConnectionInterface \$con = null";
        }
        // we want to reindex the array, so array_ functions are not the best choice
        $script .= ")
    {
        \$targetArray = array();
        foreach (\$this->get$cfc(";
        if ($col->isLazyLoad()) {
            $script .= "\$con";
        }
        $script .= ") as \$element) {
            if (\$element != \$value) {
                \$targetArray []= \$element;
            }
        }
        \$this->set$cfc(\$targetArray);

        return \$this;
    } // remove$singularPhpName()
";
    }

    /**
     * Adds a setter for Enum columns.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     * @see parent::addColumnMutators()
     */
    protected function addEnumMutator(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();
        $this->addMutatorOpen($script, $col);

        $script .= "
        if (\$v !== null) {
            \$valueSet = " . $this->getTableMapClassName() . "::getValueSet(" . $this->getColumnConstant($col) . ");
            if (!in_array(\$v, \$valueSet)) {
                throw new PropelException(sprintf('Value \"%s\" is not accepted in this enumerated column', \$v));
            }
            \$v = array_search(\$v, \$valueSet);
        }

        if (\$this->$clo !== \$v) {
            \$this->$clo = \$v;
            \$this->modifiedColumns[] = ".$this->getColumnConstant($col).";
        }
";
        $this->addMutatorClose($script, $col);
    }

    /**
     * Adds setter method for boolean columns.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     * @see parent::addColumnMutators()
     */
    protected function addBooleanMutator(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();

        $this->addBooleanMutatorComment($script, $col);
        $this->addMutatorOpenOpen($script, $col);
        $this->addMutatorOpenBody($script, $col);

        $script .= "
        if (\$v !== null) {
            if (is_string(\$v)) {
                \$v = in_array(strtolower(\$v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                \$v = (boolean) \$v;
            }
        }

        if (\$this->$clo !== \$v) {
            \$this->$clo = \$v;
            \$this->modifiedColumns[] = ".$this->getColumnConstant($col).";
        }
";
        $this->addMutatorClose($script, $col);
    }

    public function addBooleanMutatorComment(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();

        $script .= "
    /**
     * Sets the value of the [$clo] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * ".$col->getDescription()."
     * @param      boolean|integer|string \$v The new value
     * @return   ".$this->getObjectClassName(true)." The current object (for fluent API support)
     */";
    }

    /**
     * Adds setter method for "normal" columns.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     * @see parent::addColumnMutators()
     */
    protected function addDefaultMutator(&$script, Column $col)
    {
        $clo = $col->getLowercasedName();

        $this->addMutatorOpen($script, $col);

        // Perform type-casting to ensure that we can use type-sensitive
        // checking in mutators.
        if ($col->isPhpPrimitiveType()) {
            $script .= "
        if (\$v !== null) {
            \$v = (".$col->getPhpType().") \$v;
        }
";
        }

        $script .= "
        if (\$this->$clo !== \$v) {
            \$this->$clo = \$v;
            \$this->modifiedColumns[] = ".$this->getColumnConstant($col).";
        }
";
        $this->addMutatorClose($script, $col);
    }

    /**
     * Adds the hasOnlyDefaultValues() method.
     * @param string &$script The script will be modified in this method.
     */
    protected function addHasOnlyDefaultValues(&$script)
    {
        $this->addHasOnlyDefaultValuesComment($script);
        $this->addHasOnlyDefaultValuesOpen($script);
        $this->addHasOnlyDefaultValuesBody($script);
        $this->addHasOnlyDefaultValuesClose($script);
    }

    /**
     * Adds the comment for the hasOnlyDefaultValues method
     * @param string &$script The script will be modified in this method.
     * @see addHasOnlyDefaultValues
     **/
    protected function addHasOnlyDefaultValuesComment(&$script)
    {
        $script .= "
    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */";
    }

    /**
     * Adds the function declaration for the hasOnlyDefaultValues method
     * @param string &$script The script will be modified in this method.
     * @see addHasOnlyDefaultValues
     **/
    protected function addHasOnlyDefaultValuesOpen(&$script)
    {
        $script .= "
    public function hasOnlyDefaultValues()
    {";
    }

    /**
     * Adds the function body for the hasOnlyDefaultValues method
     * @param string &$script The script will be modified in this method.
     * @see addHasOnlyDefaultValues
     **/
    protected function addHasOnlyDefaultValuesBody(&$script)
    {
        $table = $this->getTable();
        $colsWithDefaults = array();
        foreach ($table->getColumns() as $col) {
            $def = $col->getDefaultValue();
            if ($def !== null && !$def->isExpression()) {
                $colsWithDefaults[] = $col;
            }
        }

        foreach ($colsWithDefaults as $col) {

            $clo = $col->getLowercasedName();
            $accessor = "\$this->$clo";
            if ($col->isTemporalType()) {
                $fmt = $this->getTemporalFormatter($col);
                $accessor = "\$this->$clo && \$this->{$clo}->format('$fmt')";
            }
            $script .= "
            if ($accessor !== " . $this->getDefaultValueString($col).") {
                return false;
            }
";
        }
    }

    /**
     * Adds the function close for the hasOnlyDefaultValues method
     * @param string &$script The script will be modified in this method.
     * @see addHasOnlyDefaultValues
     **/
    protected function addHasOnlyDefaultValuesClose(&$script)
    {
        $script .= "
        // otherwise, everything was equal, so return TRUE
        return true;";
        $script .= "
    } // hasOnlyDefaultValues()
";
    }

    /**
     * Adds the hydrate() method, which sets attributes of the object based on a ResultSet.
     * @param string &$script The script will be modified in this method.
     */
    protected function addHydrate(&$script)
    {
        $this->addHydrateComment($script);
        $this->addHydrateOpen($script);
        $this->addHydrateBody($script);
        $this->addHydrateClose($script);
    }

    /**
     * Adds the comment for the hydrate method
     * @param string &$script The script will be modified in this method.
     * @see addHydrate()
     */
    protected function addHydrateComment(&$script)
    {
        $script .= "
    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based \"start column\") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   \$row       The row returned by DataFetcher->fetch().
     * @param int     \$startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean \$rehydrate Whether this object is being re-hydrated from the database.
     * @param string  \$indexType The index type of \$row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */";
    }

    /**
     * Adds the function declaration for the hydrate method
     * @param string &$script The script will be modified in this method.
     * @see addHydrate()
     */
    protected function addHydrateOpen(&$script)
    {
        $script .= "
    public function hydrate(\$row, \$startcol = 0, \$rehydrate = false, \$indexType = TableMap::TYPE_NUM)
    {";
    }

    /**
     * Adds the function body for the hydrate method
     * @param string &$script The script will be modified in this method.
     * @see addHydrate()
     */
    protected function addHydrateBody(&$script)
    {
        $table = $this->getTable();
        $platform = $this->getPlatform();

        $tableMap = $this->getTableMapClassName();

        $script .= "
        try {
";
        $n = 0;
        foreach ($table->getColumns() as $col) {
            if (!$col->isLazyLoad()) {
                $indexName = "TableMap::TYPE_NUM == \$indexType ? $n + \$startcol : $tableMap::translateFieldName('{$col->getPhpName()}', TableMap::TYPE_PHPNAME, \$indexType)";

                $script .= "

            \$col = \$row[$indexName];";
                $clo = $col->getLowercasedName();
                if ($col->getType() === PropelTypes::CLOB_EMU && $this->getPlatform() instanceof OraclePlatform) {
                    // PDO_OCI returns a stream for CLOB objects, while other PDO adapters return a string...
                    $script .= "
            \$this->$clo = stream_get_contents(\$col);";
                } elseif ($col->isLobType() && !$platform->hasStreamBlobImpl()) {
                    $script .= "
            if (null !== \$col) {
                \$this->$clo = fopen('php://memory', 'r+');
                fwrite(\$this->$clo, \$col);
                rewind(\$this->$clo);
            } else {
                \$this->$clo = null;
            }";
                } elseif ($col->isTemporalType()) {
                    $dateTimeClass = $this->getBuildProperty('dateTimeClass');
                    if (!$dateTimeClass) {
                        $dateTimeClass = '\DateTime';
                    }
                    $handleMysqlDate = false;
                    if ($this->getPlatform() instanceof MysqlPlatform) {
                        if ($col->getType() === PropelTypes::TIMESTAMP) {
                            $handleMysqlDate = true;
                            $mysqlInvalidDateString = '0000-00-00 00:00:00';
                        } elseif ($col->getType() === PropelTypes::DATE) {
                            $handleMysqlDate = true;
                            $mysqlInvalidDateString = '0000-00-00';
                        }
                        // 00:00:00 is a valid time, so no need to check for that.
                    }
                    if ($handleMysqlDate) {
                        $script .= "
            if (\$col === '$mysqlInvalidDateString') {
                \$col = null;
            }";
                    }
                    $script .= "
            \$this->$clo = (null !== \$col) ? PropelDateTime::newInstance(\$col, null, '$dateTimeClass') : null;";
                } elseif ($col->isPhpPrimitiveType()) {
                    $script .= "
            \$this->$clo = (null !== \$col) ? (".$col->getPhpType().") \$col : null;";
                } elseif ($col->getType() === PropelTypes::OBJECT) {
                    $script .= "
            \$this->$clo = \$col;";
                } elseif ($col->getType() === PropelTypes::PHP_ARRAY) {
                    $cloUnserialized = $clo . '_unserialized';
                    $script .= "
            \$this->$clo = \$col;
            \$this->$cloUnserialized = null;";
                } elseif ($col->isPhpObjectType()) {
                    $script .= "
            \$this->$clo = (null !== \$col) ? new ".$col->getPhpType()."(\$col) : null;";
                } else {
                    $script .= "
            \$this->$clo = \$col;";
                }
                $n++;
            } // if col->isLazyLoad()
        } /* foreach */

        if ($this->getBuildProperty("addSaveMethod")) {
            $script .= "
            \$this->resetModified();
";
        }

        $script .= "
            \$this->setNew(false);

            if (\$rehydrate) {
                \$this->ensureConsistency();
            }

            return \$startcol + $n; // $n = ".$this->getTableMapClass()."::NUM_HYDRATE_COLUMNS.

        } catch (Exception \$e) {
            throw new PropelException(\"Error populating ".$this->getStubObjectBuilder()->getClassName()." object\", 0, \$e);
        }";
    }

    /**
     * Adds the function close for the hydrate method
     * @param string &$script The script will be modified in this method.
     * @see addHydrate()
     */
    protected function addHydrateClose(&$script)
    {
        $script .= "
    }
";
    }

    /**
     * Adds the buildPkeyCriteria method
     * @param string &$script The script will be modified in this method.
     **/
    protected function addBuildPkeyCriteria(&$script)
    {
        $this->addBuildPkeyCriteriaComment($script);
        $this->addBuildPkeyCriteriaOpen($script);
        $this->addBuildPkeyCriteriaBody($script);
        $this->addBuildPkeyCriteriaClose($script);
    }

    /**
     * Adds the comment for the buildPkeyCriteria method
     * @param string &$script The script will be modified in this method.
     * @see addBuildPkeyCriteria()
     **/
    protected function addBuildPkeyCriteriaComment(&$script)
    {
        $script .= "
    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */";
    }

    /**
     * Adds the function declaration for the buildPkeyCriteria method
     * @param string &$script The script will be modified in this method.
     * @see addBuildPkeyCriteria()
     **/
    protected function addBuildPkeyCriteriaOpen(&$script)
    {
        $script .= "
    public function buildPkeyCriteria()
    {";
    }

    /**
     * Adds the function body for the buildPkeyCriteria method
     * @param string &$script The script will be modified in this method.
     * @see addBuildPkeyCriteria()
     **/
    protected function addBuildPkeyCriteriaBody(&$script)
    {
        $script .= "
        \$criteria = new Criteria(".$this->getTableMapClass()."::DATABASE_NAME);";
        foreach ($this->getTable()->getPrimaryKey() as $col) {
            $clo = $col->getLowercasedName();
            $script .= "
        \$criteria->add(".$this->getColumnConstant($col).", \$this->$clo);";
        }
    }

    /**
     * Adds the function close for the buildPkeyCriteria method
     * @param string &$script The script will be modified in this method.
     * @see addBuildPkeyCriteria()
     **/
    protected function addBuildPkeyCriteriaClose(&$script)
    {
        $script .= "

        return \$criteria;
    }
";
    }

    /**
     * Adds the buildCriteria method
     * @param string &$script The script will be modified in this method.
     **/
    protected function addBuildCriteria(&$script)
    {
        $this->addBuildCriteriaComment($script);
        $this->addBuildCriteriaOpen($script);
        $this->addBuildCriteriaBody($script);
        $this->addBuildCriteriaClose($script);
    }

    /**
     * Adds comment for the buildCriteria method
     * @param string &$script The script will be modified in this method.
     * @see addBuildCriteria()
     **/
    protected function addBuildCriteriaComment(&$script)
    {
        $script .= "
    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */";
    }

    /**
     * Adds the function declaration of the buildCriteria method
     * @param string &$script The script will be modified in this method.
     * @see addBuildCriteria()
     **/
    protected function addBuildCriteriaOpen(&$script)
    {
        $script .= "
    public function buildCriteria()
    {";
    }

    /**
     * Adds the function body of the buildCriteria method
     * @param string &$script The script will be modified in this method.
     * @see addBuildCriteria()
     **/
    protected function addBuildCriteriaBody(&$script)
    {
        $script .= "
        \$criteria = new Criteria(".$this->getTableMapClass()."::DATABASE_NAME);
";
        foreach ($this->getTable()->getColumns() as $col) {
            $clo = $col->getLowercasedName();
            $script .= "
        if (\$this->isColumnModified(".$this->getColumnConstant($col).")) \$criteria->add(".$this->getColumnConstant($col).", \$this->$clo);";
        }
    }

    /**
     * Adds the function close of the buildCriteria method
     * @param string &$script The script will be modified in this method.
     * @see addBuildCriteria()
     **/
    protected function addBuildCriteriaClose(&$script)
    {
        $script .= "

        return \$criteria;
    }
";
    }

    /**
     * Adds the toArray method
     * @param string &$script The script will be modified in this method.
     **/
    protected function addToArray(&$script)
    {
        $fks = $this->getTable()->getForeignKeys();
        $referrers = $this->getTable()->getReferrers();
        $hasFks = count($fks) > 0 || count($referrers) > 0;
        $objectClassName = $this->getUnqualifiedClassName();
        $pkGetter = $this->getTable()->hasCompositePrimaryKey() ? 'serialize($this->getPrimaryKey())' : '$this->getPrimaryKey()';
        $defaultKeyType = $this->getDefaultKeyType();
        $script .= "
    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  \$keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::$defaultKeyType.
     * @param     boolean \$includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array \$alreadyDumpedObjects List of objects to skip to avoid recursion";
        if ($hasFks) {
            $script .= "
     * @param     boolean \$includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.";
        }
        $script .= "
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray(\$keyType = TableMap::$defaultKeyType, \$includeLazyLoadColumns = true, \$alreadyDumpedObjects = array()" . ($hasFks ? ", \$includeForeignObjects = false" : '') . ")
    {
        if (isset(\$alreadyDumpedObjects['$objectClassName'][$pkGetter])) {
            return '*RECURSION*';
        }
        \$alreadyDumpedObjects['$objectClassName'][$pkGetter] = true;
        \$keys = ".$this->getTableMapClassName()."::getFieldNames(\$keyType);
        \$result = array(";
        foreach ($this->getTable()->getColumns() as $num => $col) {
            if ($col->isLazyLoad()) {
                $script .= "
            \$keys[$num] => (\$includeLazyLoadColumns) ? \$this->get".$col->getPhpName()."() : null,";
            } else {
                $script .= "
            \$keys[$num] => \$this->get".$col->getPhpName()."(),";
            }
        }
        $script .= "
        );";
        $script .= "
        \$virtualColumns = \$this->virtualColumns;
        foreach (\$virtualColumns as \$key => \$virtualColumn) {
            \$result[\$key] = \$virtualColumn;
        }
        ";
        if ($hasFks) {
            $script .= "
        if (\$includeForeignObjects) {";
            foreach ($fks as $fk) {
                $script .= "
            if (null !== \$this->" . $this->getFKVarName($fk) . ") {
                \$result['" . $this->getFKPhpNameAffix($fk, false) . "'] = \$this->" . $this->getFKVarName($fk) . "->toArray(\$keyType, \$includeLazyLoadColumns,  \$alreadyDumpedObjects, true);
            }";
            }
            foreach ($referrers as $fk) {
                if ($fk->isLocalPrimaryKey()) {
                    $script .= "
            if (null !== \$this->" . $this->getPKRefFKVarName($fk) . ") {
                \$result['" . $this->getRefFKPhpNameAffix($fk, false) . "'] = \$this->" . $this->getPKRefFKVarName($fk) . "->toArray(\$keyType, \$includeLazyLoadColumns, \$alreadyDumpedObjects, true);
            }";
                } else {
                    $script .= "
            if (null !== \$this->" . $this->getRefFKCollVarName($fk) . ") {
                \$result['" . $this->getRefFKPhpNameAffix($fk, true) . "'] = \$this->" . $this->getRefFKCollVarName($fk) . "->toArray(null, true, \$keyType, \$includeLazyLoadColumns, \$alreadyDumpedObjects);
            }";
                }
            }
            $script .= "
        }";
        }
        $script .= "

        return \$result;
    }
";
    } // addToArray()

    /**
     * Adds the getByName method
     * @param string &$script The script will be modified in this method.
     **/
    protected function addGetByName(&$script)
    {
        $this->addGetByNameComment($script);
        $this->addGetByNameOpen($script);
        $this->addGetByNameBody($script);
        $this->addGetByNameClose($script);
    }

    /**
     * Adds the comment for the getByName method
     * @param string &$script The script will be modified in this method.
     * @see addGetByName
     **/
    protected function addGetByNameComment(&$script)
    {
        $defaultKeyType = $this->getDefaultKeyType();
        $script .= "
    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string \$name name
     * @param      string \$type The type of fieldname the \$name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::$defaultKeyType.
     * @return mixed Value of field.
     */";
    }

    /**
     * Adds the function declaration for the getByName method
     * @param string &$script The script will be modified in this method.
     * @see addGetByName
     **/
    protected function addGetByNameOpen(&$script)
    {
        $defaultKeyType = $this->getDefaultKeyType();
        $script .= "
    public function getByName(\$name, \$type = TableMap::$defaultKeyType)
    {";
    }

    /**
     * Adds the function body for the getByName method
     * @param string &$script The script will be modified in this method.
     * @see addGetByName
     **/
    protected function addGetByNameBody(&$script)
    {
        $script .= "
        \$pos = ".$this->getTableMapClassName()."::translateFieldName(\$name, \$type, TableMap::TYPE_NUM);
        \$field = \$this->getByPosition(\$pos);";
    }

    /**
     * Adds the function close for the getByName method
     * @param string &$script The script will be modified in this method.
     * @see addGetByName
     **/
    protected function addGetByNameClose(&$script)
    {
        $script .= "

        return \$field;
    }
";
    }

    /**
     * Adds the getByPosition method
     * @param string &$script The script will be modified in this method.
     **/
    protected function addGetByPosition(&$script)
    {
        $this->addGetByPositionComment($script);
        $this->addGetByPositionOpen($script);
        $this->addGetByPositionBody($script);
        $this->addGetByPositionClose($script);
    }

    /**
     * Adds comment for the getByPosition method
     * @param string &$script The script will be modified in this method.
     * @see addGetByPosition
     **/
    protected function addGetByPositionComment(&$script)
    {
        $script .= "
    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int \$pos position in xml schema
     * @return mixed Value of field at \$pos
     */";
    }

    /**
     * Adds the function declaration for the getByPosition method
     * @param string &$script The script will be modified in this method.
     * @see addGetByPosition
     **/
    protected function addGetByPositionOpen(&$script)
    {
        $script .= "
    public function getByPosition(\$pos)
    {";
    }

    /**
     * Adds the function body for the getByPosition method
     * @param string &$script The script will be modified in this method.
     * @see addGetByPosition
     **/
    protected function addGetByPositionBody(&$script)
    {
        $table = $this->getTable();
        $script .= "
        switch (\$pos) {";
        $i = 0;
        foreach ($table->getColumns() as $col) {
            $cfc = $col->getPhpName();
            $script .= "
            case $i:
                return \$this->get$cfc();
                break;";
            $i++;
        } /* foreach */
        $script .= "
            default:
                return null;
                break;
        } // switch()";
    }

    /**
     * Adds the function close for the getByPosition method
     * @param string &$script The script will be modified in this method.
     * @see addGetByPosition
     **/
    protected function addGetByPositionClose(&$script)
    {
        $script .= "
    }
";
    }

    protected function addSetByName(&$script)
    {
        $defaultKeyType = $this->getDefaultKeyType();
        $script .= "
    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string \$name
     * @param      mixed  \$value field value
     * @param      string \$type The type of fieldname the \$name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::$defaultKeyType.
     * @return void
     */
    public function setByName(\$name, \$value, \$type = TableMap::$defaultKeyType)
    {
        \$pos = ".$this->getTableMapClassName()."::translateFieldName(\$name, \$type, TableMap::TYPE_NUM);

        return \$this->setByPosition(\$pos, \$value);
    }
";
    }

    protected function addSetByPosition(&$script)
    {
        $table = $this->getTable();
        $script .= "
    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int \$pos position in xml schema
     * @param      mixed \$value field value
     * @return void
     */
    public function setByPosition(\$pos, \$value)
    {
        switch (\$pos) {";
        $i = 0;
        foreach ($table->getColumns() as $col) {
            $cfc = $col->getPhpName();

            $script .= "
            case $i:";

            if (PropelTypes::ENUM === $col->getType()) {
                $script .= "
                \$valueSet = " . $this->getTableMapClassName() . "::getValueSet(" . $this->getColumnConstant($col) . ");
                if (isset(\$valueSet[\$value])) {
                    \$value = \$valueSet[\$value];
                }";
            } elseif (PropelTypes::PHP_ARRAY === $col->getType()) {
                $script .= "
                if (!is_array(\$value)) {
                    \$v = trim(substr(\$value, 2, -2));
                    \$value = \$v ? explode(' | ', \$v) : array();
                }";
            }

            $script .= "
                \$this->set$cfc(\$value);
                break;";
            $i++;
        } /* foreach */
        $script .= "
        } // switch()
    }
";
    }

    protected function addFromArray(&$script)
    {
        $defaultKeyType = $this->getDefaultKeyType();
        $table = $this->getTable();
        $script .= "
    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. \$_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::$defaultKeyType.
     *
     * @param      array  \$arr     An array to populate the object from.
     * @param      string \$keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray(\$arr, \$keyType = TableMap::$defaultKeyType)
    {
        \$keys = ".$this->getTableMapClassName()."::getFieldNames(\$keyType);
";
        foreach ($table->getColumns() as $num => $col) {
            $cfc = $col->getPhpName();
            $script .= "
        if (array_key_exists(\$keys[$num], \$arr)) \$this->set$cfc(\$arr[\$keys[$num]]);";
        } /* foreach */
        $script .= "
    }
";
    }

    /**
     * Adds a delete() method to remove the object form the datastore.
     * @param string &$script The script will be modified in this method.
     */
    protected function addDelete(&$script)
    {
        $this->addDeleteComment($script);
        $this->addDeleteOpen($script);
        $this->addDeleteBody($script);
        $this->addDeleteClose($script);
    }

    /**
     * Adds the comment for the delete function
     * @param string &$script The script will be modified in this method.
     * @see addDelete()
     **/
    protected function addDeleteComment(&$script)
    {
        $className = $this->getUnqualifiedClassName();
        $script .= "
    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface \$con
     * @return void
     * @throws PropelException
     * @see $className::setDeleted()
     * @see $className::isDeleted()
     */";
    }

    /**
     * Adds the function declaration for the delete function
     * @param string &$script The script will be modified in this method.
     * @see addDelete()
     **/
    protected function addDeleteOpen(&$script)
    {
        $script .= "
    public function delete(ConnectionInterface \$con = null)
    {";
    }

    /**
     * Adds the function body for the delete function
     * @param string &$script The script will be modified in this method.
     * @see addDelete()
     **/
    protected function addDeleteBody(&$script)
    {
        $script .= "
        if (\$this->isDeleted()) {
            throw new PropelException(\"This object has already been deleted.\");
        }

        if (\$con === null) {
            \$con = Propel::getServiceContainer()->getWriteConnection(".$this->getTableMapClass()."::DATABASE_NAME);
        }

        \$con->beginTransaction();
        try {
            \$deleteQuery = ".$this->getQueryClassName()."::create()
                ->filterByPrimaryKey(\$this->getPrimaryKey());";
        if ($this->getGeneratorConfig()->getBuildProperty('addHooks')) {
            $script .= "
            \$ret = \$this->preDelete(\$con);";
            // apply behaviors
            $this->applyBehaviorModifier('preDelete', $script, "            ");
            $script .= "
            if (\$ret) {
                \$deleteQuery->delete(\$con);
                \$this->postDelete(\$con);";
            // apply behaviors
            $this->applyBehaviorModifier('postDelete', $script, "                ");
            $script .= "
                \$con->commit();
                \$this->setDeleted(true);
            } else {
                \$con->commit();
            }";
        } else {
            // apply behaviors
            $this->applyBehaviorModifier('preDelete', $script, "            ");
            $script .= "
            \$deleteQuery->delete(\$con);";
            // apply behaviors
            $this->applyBehaviorModifier('postDelete', $script, "            ");
            $script .= "
            \$con->commit();
            \$this->setDeleted(true);";
        }

        $script .= "
        } catch (Exception \$e) {
            \$con->rollBack();
            throw \$e;
        }";
    }

    /**
     * Adds the function close for the delete function
     * @param string &$script The script will be modified in this method.
     * @see addDelete()
     **/
    protected function addDeleteClose(&$script)
    {
        $script .= "
    }
";
    } // addDelete()

    /**
     * Adds a reload() method to re-fetch the data for this object from the database.
     * @param string &$script The script will be modified in this method.
     */
    protected function addReload(&$script)
    {
        $table = $this->getTable();
        $script .= "
    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean \$deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface \$con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload(\$deep = false, ConnectionInterface \$con = null)
    {
        if (\$this->isDeleted()) {
            throw new PropelException(\"Cannot reload a deleted object.\");
        }

        if (\$this->isNew()) {
            throw new PropelException(\"Cannot reload an unsaved object.\");
        }

        if (\$con === null) {
            \$con = Propel::getServiceContainer()->getReadConnection(".$this->getTableMapClass()."::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        \$dataFetcher = ".$this->getQueryClassName()."::create(null, \$this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find(\$con);
        \$row = \$dataFetcher->fetch();
        \$dataFetcher->close();
        if (!\$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        \$this->hydrate(\$row, 0, true, \$dataFetcher->getIndexType()); // rehydrate
";

        // support for lazy load columns
        foreach ($table->getColumns() as $col) {
            if ($col->isLazyLoad()) {
                $clo = $col->getLowercasedName();
                $script .= "
        // Reset the $clo lazy-load column
        \$this->" . $clo . " = null;
        \$this->".$clo."_isLoaded = false;
";
            }
        }

        $script .= "
        if (\$deep) {  // also de-associate any related objects?
";

        foreach ($table->getForeignKeys() as $fk) {
            $varName = $this->getFKVarName($fk);
            $script .= "
            \$this->".$varName." = null;";
        }

        foreach ($table->getReferrers() as $refFK) {
            if ($refFK->isLocalPrimaryKey()) {
                $script .= "
            \$this->".$this->getPKRefFKVarName($refFK)." = null;
";
            } else {
                $script .= "
            \$this->".$this->getRefFKCollVarName($refFK)." = null;
";
            }
        }

        foreach ($table->getCrossFks() as $fkList) {
            list($refFK, $crossFK) = $fkList;
            $script .= "
            \$this->" . $this->getCrossFKVarName($crossFK). " = null;";
        }

        $script .= "
        } // if (deep)
    }
";
    } // addReload()

    /**
     * Adds the methods related to refreshing, saving and deleting the object.
     * @param string &$script The script will be modified in this method.
     */
    protected function addManipulationMethods(&$script)
    {
        $this->addReload($script);
        $this->addDelete($script);
        $this->addSave($script);
        $this->addDoSave($script);
        $script .= $this->addDoInsert();
        $script .= $this->addDoUpdate();
    }

    /**
     * Adds the correct getPrimaryKey() method for this object.
     * @param string &$script The script will be modified in this method.
     */
    protected function addGetPrimaryKey(&$script)
    {
        $pkeys = $this->getTable()->getPrimaryKey();
        if (count($pkeys) == 1) {
            $this->addGetPrimaryKey_SinglePK($script);
        } elseif (count($pkeys) > 1) {
            $this->addGetPrimaryKey_MultiPK($script);
        } else {
            // no primary key -- this is deprecated, since we don't *need* this method anymore
            $this->addGetPrimaryKey_NoPK($script);
        }
    }

    /**
     * Adds the getPrimaryKey() method for tables that contain a single-column primary key.
     * @param string &$script The script will be modified in this method.
     */
    protected function addGetPrimaryKey_SinglePK(&$script)
    {
        $table = $this->getTable();
        $pkeys = $table->getPrimaryKey();
        $cptype = $pkeys[0]->getPhpType();

        $script .= "
    /**
     * Returns the primary key for this object (row).
     * @return   $cptype
     */
    public function getPrimaryKey()
    {
        return \$this->get".$pkeys[0]->getPhpName()."();
    }
";
    } // addetPrimaryKey_SingleFK

    /**
     * Adds the setPrimaryKey() method for tables that contain a multi-column primary key.
     * @param string &$script The script will be modified in this method.
     */
    protected function addGetPrimaryKey_MultiPK(&$script)
    {

        $script .= "
    /**
     * Returns the composite primary key for this object.
     * The array elements will be in same order as specified in XML.
     * @return array
     */
    public function getPrimaryKey()
    {
        \$pks = array();";
        $i = 0;
        foreach ($this->getTable()->getPrimaryKey() as $pk) {
            $script .= "
        \$pks[$i] = \$this->get".$pk->getPhpName()."();";
            $i++;
        } /* foreach */
        $script .= "

        return \$pks;
    }
";
    } // addGetPrimaryKey_MultiFK()

    /**
     * Adds the getPrimaryKey() method for objects that have no primary key.
     * This "feature" is deprecated, since the getPrimaryKey() method is not required
     * by the Persistent interface (or used by the templates).  Hence, this method is also
     * deprecated.
     * @param string &$script The script will be modified in this method.
     * @deprecated
     */
    protected function addGetPrimaryKey_NoPK(&$script)
    {
        $script .= "
    /**
     * Returns NULL since this table doesn't have a primary key.
     * This method exists only for BC and is deprecated!
     * @return null
     */
    public function getPrimaryKey()
    {
        return null;
    }
";
    }

    /**
     * Adds the correct setPrimaryKey() method for this object.
     * @param string &$script The script will be modified in this method.
     */
    protected function addSetPrimaryKey(&$script)
    {
        $pkeys = $this->getTable()->getPrimaryKey();
        if (count($pkeys) == 1) {
            $this->addSetPrimaryKey_SinglePK($script);
        } elseif (count($pkeys) > 1) {
            $this->addSetPrimaryKey_MultiPK($script);
        } else {
            // no primary key -- this is deprecated, since we don't *need* this method anymore
            $this->addSetPrimaryKey_NoPK($script);
        }
    }

    /**
     * Adds the setPrimaryKey() method for tables that contain a single-column primary key.
     * @param string &$script The script will be modified in this method.
     */
    protected function addSetPrimaryKey_SinglePK(&$script)
    {

        $pkeys = $this->getTable()->getPrimaryKey();
        $col = $pkeys[0];
        $clo=$col->getLowercasedName();
        $ctype = $col->getPhpType();

        $script .= "
    /**
     * Generic method to set the primary key ($clo column).
     *
     * @param       $ctype \$key Primary key.
     * @return void
     */
    public function setPrimaryKey(\$key)
    {
        \$this->set".$col->getPhpName()."(\$key);
    }
";
    } // addSetPrimaryKey_SinglePK

    /**
     * Adds the setPrimaryKey() method for tables that contain a multi-columnprimary key.
     * @param string &$script The script will be modified in this method.
     */
    protected function addSetPrimaryKey_MultiPK(&$script)
    {

        $script .="
    /**
     * Set the [composite] primary key.
     *
     * @param      array \$keys The elements of the composite key (order must match the order in XML file).
     * @return void
     */
    public function setPrimaryKey(\$keys)
    {";
        $i = 0;
        foreach ($this->getTable()->getPrimaryKey() as $pk) {
            $script .= "
        \$this->set".$pk->getPhpName()."(\$keys[$i]);";
            $i++;
        }
        $script .= "
    }
";
    }

    /**
     * Adds the setPrimaryKey() method for objects that have no primary key.
     * This "feature" is deprecated, since the setPrimaryKey() method is not required
     * by the Persistent interface (or used by the templates).  Hence, this method is also
     * deprecated.
     * @param string &$script The script will be modified in this method.
     * @deprecated
     */
    protected function addSetPrimaryKey_NoPK(&$script)
    {
        $script .="
    /**
     * Dummy primary key setter.
     *
     * This function only exists to preserve backwards compatibility.  It is no longer
     * needed or required by the Persistent interface.  It will be removed in next BC-breaking
     * release of Propel.
     *
     * @deprecated
     */
    public function setPrimaryKey(\$pk)
    {
        // do nothing, because this object doesn't have any primary keys
    }
";
    }

    /**
     * Adds the isPrimaryKeyNull() method
     * @param string &$script The script will be modified in this method.
     */
    protected function addIsPrimaryKeyNull(&$script)
    {
        $table = $this->getTable();
        $pkeys = $table->getPrimaryKey();

        $script .= "
    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {";
        if (count($pkeys) == 1) {
            $script .= "

        return null === \$this->get" . $pkeys[0]->getPhpName() . "();";
        } else {
            $tests = array();
            foreach ($pkeys as $pkey) {
                $tests[]= "(null === \$this->get" . $pkey->getPhpName() . "())";
            }
            $script .= "

        return " . join(' && ', $tests) . ";";
        }
        $script .= "
    }
";
    }

    /**
     * Constructs variable name for fkey-related objects.
     * @param  ForeignKey $fk
     * @return string
     */
    public function getFKVarName(ForeignKey $fk)
    {
        return 'a' . $this->getFKPhpNameAffix($fk, false);
    }

    /**
     * Constructs variable name for objects which referencing current table by specified foreign key.
     * @param  ForeignKey $fk
     * @return string
     */
    public function getRefFKCollVarName(ForeignKey $fk)
    {
        return 'coll' . $this->getRefFKPhpNameAffix($fk, true);
    }

    /**
     * Constructs variable name for single object which references current table by specified foreign key
     * which is ALSO a primary key (hence one-to-one relationship).
     * @param  ForeignKey $fk
     * @return string
     */
    public function getPKRefFKVarName(ForeignKey $fk)
    {
        return 'single' . $this->getRefFKPhpNameAffix($fk, false);
    }

    /**
     * Adds the methods that get & set objects related by foreign key to the current object.
     * @param string &$script The script will be modified in this method.
     */
    protected function addFKMethods(&$script)
    {
        foreach ($this->getTable()->getForeignKeys() as $fk) {
            $this->declareClassFromBuilder($this->getNewStubObjectBuilder($fk->getForeignTable()), 'Child');
            $this->declareClassFromBuilder($this->getNewStubQueryBuilder($fk->getForeignTable()));
            $this->addFKMutator($script, $fk);
            $this->addFKAccessor($script, $fk);
        } // foreach fk
    }

    /**
     * Adds the class attributes that are needed to store fkey related objects.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $fk
     */
    protected function addFKAttributes(&$script, ForeignKey $fk)
    {
        $className = $fk->getForeignTable()->getPhpName();
        $varName = $this->getFKVarName($fk);

        $script .= "
    /**
     * @var        $className
     */
    protected $".$varName.";
";
    }

    /**
     * Adds the mutator (setter) method for setting an fkey related object.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $fk
     */
    protected function addFKMutator(&$script, ForeignKey $fk)
    {
        $table = $this->getTable();
        $fkTable = $fk->getForeignTable();

        $joinTableObjectBuilder = $this->getNewObjectBuilder($fkTable);
        $className = $joinTableObjectBuilder->getObjectClassName();

        $varName = $this->getFKVarName($fk);

        $script .= "
    /**
     * Declares an association between this object and a $className object.
     *
     * @param                  $className \$v
     * @return                 ".$this->getObjectClassName(true)." The current object (for fluent API support)
     * @throws PropelException
     */
    public function set".$this->getFKPhpNameAffix($fk, false)."($className \$v = null)
    {";
        foreach ($fk->getLocalColumns() as $columnName) {
            $column = $table->getColumn($columnName);
            $lfmap = $fk->getLocalForeignMapping();
            $colFKName = $lfmap[$columnName];
            $colFK = $fkTable->getColumn($colFKName);
            $script .= "
        if (\$v === null) {
            \$this->set".$column->getPhpName()."(".$this->getDefaultValueString($column).");
        } else {
            \$this->set".$column->getPhpName()."(\$v->get".$colFK->getPhpName()."());
        }
";

        } /* foreach local col */

        $script .= "
        \$this->$varName = \$v;
";

        // Now add bi-directional relationship binding, taking into account whether this is
        // a one-to-one relationship.

        if ($fk->isLocalPrimaryKey()) {
            $script .= "
        // Add binding for other direction of this 1:1 relationship.
        if (\$v !== null) {
            \$v->set".$this->getRefFKPhpNameAffix($fk, false)."(\$this);
        }
";
        } else {
            $script .= "
        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the $className object, it will not be re-added.
        if (\$v !== null) {
            \$v->add".$this->getRefFKPhpNameAffix($fk, false)."(\$this);
        }
";

        }

        $script .= "

        return \$this;
    }
";
    }

    /**
     * Adds the accessor (getter) method for getting an fkey related object.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $fk
     */
    protected function addFKAccessor(&$script, ForeignKey $fk)
    {
        $table = $this->getTable();

        $varName = $this->getFKVarName($fk);

        $fkQueryBuilder = $this->getNewStubQueryBuilder($fk->getForeignTable());
        $fkObjectBuilder = $this->getNewObjectBuilder($fk->getForeignTable())->getStubObjectBuilder();
        $className = $this->getClassNameFromBuilder($fkObjectBuilder); // get the ClassName that has maybe a prefix

        $and = '';
        $conditional = '';
        $localColumns = array(); // foreign key local attributes names

        // If the related columns are a primary key on the foreign table
        // then use findPk() instead of doSelect() to take advantage
        // of instance pooling
        $findPk = $fk->isForeignPrimaryKey();

        foreach ($fk->getLocalColumns() as $columnName) {

            $lfmap = $fk->getLocalForeignMapping();

            $foreignColumn = $fk->getForeignTable()->getColumn($lfmap[$columnName]);

            $column = $table->getColumn($columnName);
            $cptype = $column->getPhpType();
            $clo = $column->getLowercasedName();
            $localColumns[$foreignColumn->getPosition()] = '$this->'.$clo;

            if ($cptype == "integer" || $cptype == "float" || $cptype == "double") {
                $conditional .= $and . "\$this->". $clo ." != 0";
            } elseif ($cptype == "string") {
                $conditional .= $and . "(\$this->" . $clo ." !== \"\" && \$this->".$clo." !== null)";
            } else {
                $conditional .= $and . "\$this->" . $clo ." !== null";
            }

            $and = " && ";
        }

        ksort($localColumns); // restoring the order of the foreign PK
        $localColumns = count($localColumns) > 1 ?
                ('array('.implode(', ', $localColumns).')') : reset($localColumns);

        $script .= "

    /**
     * Get the associated $className object
     *
     * @param      ConnectionInterface \$con Optional Connection object.
     * @return                 $className The associated $className object.
     * @throws PropelException
     */
    public function get".$this->getFKPhpNameAffix($fk, false)."(ConnectionInterface \$con = null)
    {";
        $script .= "
        if (\$this->$varName === null && ($conditional)) {";
        if ($findPk) {
            $script .= "
            \$this->$varName = ".$this->getClassNameFromBuilder($fkQueryBuilder)."::create()->findPk($localColumns, \$con);";
        } else {
            $script .= "
            \$this->$varName = ".$this->getClassNameFromBuilder($fkQueryBuilder)."::create()
                ->filterBy" . $this->getRefFKPhpNameAffix($fk, $plural = false) . "(\$this) // here
                ->findOne(\$con);";
        }
        if ($fk->isLocalPrimaryKey()) {
            $script .= "
            // Because this foreign key represents a one-to-one relationship, we will create a bi-directional association.
            \$this->{$varName}->set".$this->getRefFKPhpNameAffix($fk, false)."(\$this);";
        } else {
            $script .= "
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                \$this->{$varName}->add".$this->getRefFKPhpNameAffix($fk, true)."(\$this);
             */";
        }

        $script .= "
        }

        return \$this->$varName;
    }
";

    } // addFKAccessor

    /**
     * Adds a convenience method for setting a related object by specifying the primary key.
     * This can be used in conjunction with the getPrimaryKey() for systems where nothing is known
     * about the actual objects being related.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $fk
     */
    protected function addFKByKeyMutator(&$script, ForeignKey $fk)
    {
        $table = $this->getTable();

        $methodAffix = $this->getFKPhpNameAffix($fk);

        $script .= "
    /**
     * Provides convenient way to set a relationship based on a
     * key.  e.g.
     * <code>\$bar->setFooKey(\$foo->getPrimaryKey())</code>
     *";
        if (count($fk->getLocalColumns()) > 1) {
            $script .= "
     * Note: It is important that the xml schema used to create this class
     * maintains consistency in the order of related columns between
     * ".$table->getName()." and ". $fk->getName().".
     * If for some reason this is impossible, this method should be
     * overridden in <code>".$table->getPhpName()."</code>.";
        }
        $script .= "
     * @return                 ".$this->getObjectClassName(true)." The current object (for fluent API support)
     * @throws PropelException
     */
    public function set".$methodAffix."Key(\$key)
    {
";
        if (count($fk->getLocalColumns()) > 1) {
            $i = 0;
            foreach ($fk->getLocalColumns() as $colName) {
                $col = $table->getColumn($colName);
                $fktype = $col->getPhpType();
                $script .= "
            \$this->set".$col->getPhpName()."( ($fktype) \$key[$i] );
";
                $i++;
            } /* foreach */
        } else {
            $lcols = $fk->getLocalColumns();
            $colName = $lcols[0];
            $col = $table->getColumn($colName);
            $fktype = $col->getPhpType();
            $script .= "
        \$this->set".$col->getPhpName()."( ($fktype) \$key);
";
        }
        $script .= "

        return \$this;
    }
";
    } // addFKByKeyMutator()

    /**
     * Adds the method that fetches fkey-related (referencing) objects but also joins in data from another table.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKGetJoinMethods(&$script, ForeignKey $refFK)
    {
        $table = $this->getTable();
        $tblFK = $refFK->getTable();
        $joinBehavior = $this->getGeneratorConfig()->getBuildProperty('useLeftJoinsInDoJoinMethods') ? 'Criteria::LEFT_JOIN' : 'Criteria::INNER_JOIN';

        $fkQueryClassName = $this->getClassNameFromBuilder($this->getNewStubQueryBuilder($refFK->getTable()));
        $relCol = $this->getRefFKPhpNameAffix($refFK, $plural = true);

        $fkObjectBuilder = $this->getNewObjectBuilder($tblFK);
        $className = $fkObjectBuilder->getObjectClassName();

        foreach ($tblFK->getForeignKeys() as $fk2) {

            $tblFK2 = $fk2->getForeignTable();
            $doJoinGet = !$tblFK2->isForReferenceOnly();

            // it doesn't make sense to join in rows from the current table, since we are fetching
            // objects related to *this* table (i.e. the joined rows will all be the same row as current object)
            if ($this->getTable()->getPhpName() == $tblFK2->getPhpName()) {
                $doJoinGet = false;
            }

            $relCol2 = $this->getFKPhpNameAffix($fk2, false);

            if ( $this->getRelatedBySuffix($refFK) != "" &&
            ($this->getRelatedBySuffix($refFK) == $this->getRelatedBySuffix($fk2))) {
                $doJoinGet = false;
            }

            if ($doJoinGet) {
                $script .= "

    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this ".$table->getPhpName()." is new, it will return
     * an empty collection; or if this ".$table->getPhpName()." has previously
     * been saved, it will retrieve related $relCol from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in ".$table->getPhpName().".
     *
     * @param      Criteria \$criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface \$con optional connection object
     * @param      string \$joinBehavior optional join type to use (defaults to $joinBehavior)
     * @return Collection|{$className}[] List of $className objects
     */
    public function get".$relCol."Join".$relCol2."(\$criteria = null, \$con = null, \$joinBehavior = $joinBehavior)
    {";
                $script .= "
        \$query = $fkQueryClassName::create(null, \$criteria);
        \$query->joinWith('" . $this->getFKPhpNameAffix($fk2, false) . "', \$joinBehavior);

        return \$this->get". $relCol . "(\$query, \$con);
    }
";
            } /* end if ($doJoinGet) */

        } /* end foreach ($tblFK->getForeignKeys() as $fk2) { */

    } // function

    /**
     * Adds the attributes used to store objects that have referrer fkey relationships to this object.
     * <code>protected collVarName;</code>
     * <code>private lastVarNameCriteria = null;</code>
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKAttributes(&$script, ForeignKey $refFK)
    {
        $joinedTableObjectBuilder = $this->getNewObjectBuilder($refFK->getTable());
        $className = $joinedTableObjectBuilder->getObjectClassName();

        if ($refFK->isLocalPrimaryKey()) {
            $script .= "
    /**
     * @var        $className one-to-one related $className object
     */
    protected $".$this->getPKRefFKVarName($refFK).";
";
        } else {
            $script .= "
    /**
     * @var        ObjectCollection|{$className}[] Collection to store aggregation of $className objects.
     */
    protected $" . $this->getRefFKCollVarName($refFK) . ";
    protected $" . $this->getRefFKCollVarName($refFK) . "Partial;
";
        }
    }

    /**
     * Adds the methods for retrieving, initializing, adding objects that are related to this one by foreign keys.
     * @param string &$script The script will be modified in this method.
     */
    protected function addRefFKMethods(&$script)
    {
        if (!$referrers = $this->getTable()->getReferrers()) {
            return;
        }
        $this->addInitRelations($script, $referrers);
        foreach ($referrers as $refFK) {
            $this->declareClassFromBuilder($this->getNewStubObjectBuilder($refFK->getTable()), 'Child');
            $this->declareClassFromBuilder($this->getNewStubQueryBuilder($refFK->getTable()));
            if ($refFK->isLocalPrimaryKey()) {
                $this->addPKRefFKGet($script, $refFK);
                $this->addPKRefFKSet($script, $refFK);
            } else {
                $this->addRefFKClear($script, $refFK);
                $this->addRefFKPartial($script, $refFK);
                $this->addRefFKInit($script, $refFK);
                $this->addRefFKGet($script, $refFK);
                $this->addRefFKSet($script, $refFK);
                $this->addRefFKCount($script, $refFK);
                $this->addRefFKAdd($script, $refFK);
                $this->addRefFKDoAdd($script, $refFK);
                $this->addRefFKRemove($script, $refFK);
                $this->addRefFKGetJoinMethods($script, $refFK);
            }
        }
    }

    protected function addInitRelations(&$script, $referrers)
    {
        $script .= "

    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[\$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string \$relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation(\$relationName)
    {";
        foreach ($referrers as $refFK) {
            if (!$refFK->isLocalPrimaryKey()) {
                $relationName = $this->getRefFKPhpNameAffix($refFK);
                $relCol = $this->getRefFKPhpNameAffix($refFK, true);
                $script .= "
        if ('$relationName' == \$relationName) {
            return \$this->init$relCol();
        }";
            }
        }
        $script .= "
    }
";
    }

    /**
     * Adds the method that clears the referrer fkey collection.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKClear(&$script, ForeignKey $refFK)
    {
        $relCol = $this->getRefFKPhpNameAffix($refFK, true);
        $collName = $this->getRefFKCollVarName($refFK);

        $script .= "
    /**
     * Clears out the $collName collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        add$relCol()
     */
    public function clear$relCol()
    {
        \$this->$collName = null; // important to set this to NULL since that means it is uninitialized
    }
";
    } // addRefererClear()

    /**
     * Adds the method that initializes the referrer fkey collection.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKInit(&$script, ForeignKey $refFK)
    {
        $relCol = $this->getRefFKPhpNameAffix($refFK, true);
        $collName = $this->getRefFKCollVarName($refFK);

        $script .= "
    /**
     * Initializes the $collName collection.
     *
     * By default this just sets the $collName collection to an empty array (like clear$collName());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean \$overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function init$relCol(\$overrideExisting = true)
    {
        if (null !== \$this->$collName && !\$overrideExisting) {
            return;
        }
        \$this->$collName = new ObjectCollection();
        \$this->{$collName}->setModel('" . $this->getClassNameFromBuilder($this->getNewStubObjectBuilder($refFK->getTable()), true) . "');
    }
";
    } // addRefererInit()

    /**
     * Adds the method that adds an object into the referrer fkey collection.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKAdd(&$script, ForeignKey $refFK)
    {
        $tblFK = $refFK->getTable();

        $joinedTableObjectBuilder = $this->getNewObjectBuilder($refFK->getTable());
        $className = $joinedTableObjectBuilder->getObjectClassName();

        if ($tblFK->getChildrenColumn()) {
            $className = $joinedTableObjectBuilder->getFullyQualifiedClassName();
        }

        $collName = $this->getRefFKCollVarName($refFK);

        $script .= "
    /**
     * Method called to associate a $className object to this object
     * through the $className foreign key attribute.
     *
     * @param    $className \$l $className
     * @return   ".$this->getObjectClassName(true)." The current object (for fluent API support)
     */
    public function add".$this->getRefFKPhpNameAffix($refFK, false)."($className \$l)
    {
        if (\$this->$collName === null) {
            \$this->init" . $this->getRefFKPhpNameAffix($refFK, $plural = true) . "();
            \$this->{$collName}Partial = true;
        }

        if (!in_array(\$l, \$this->{$collName}->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            \$this->doAdd" . $this->getRefFKPhpNameAffix($refFK, $plural = false) . "(\$l);
        }

        return \$this;
    }
";
    } // addRefererAdd

    /**
     * Adds the method that returns the size of the referrer fkey collection.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKCount(&$script, ForeignKey $refFK)
    {
        $fkQueryClassName = $this->getClassNameFromBuilder($this->getNewStubQueryBuilder($refFK->getTable()));
        $relCol = $this->getRefFKPhpNameAffix($refFK, $plural = true);
        $collName = $this->getRefFKCollVarName($refFK);

        $joinedTableObjectBuilder = $this->getNewObjectBuilder($refFK->getTable());
        $className = $this->getClassNameFromBuilder($joinedTableObjectBuilder);

        $script .= "
    /**
     * Returns the number of related $className objects.
     *
     * @param      Criteria \$criteria
     * @param      boolean \$distinct
     * @param      ConnectionInterface \$con
     * @return int             Count of related $className objects.
     * @throws PropelException
     */
    public function count{$relCol}(Criteria \$criteria = null, \$distinct = false, ConnectionInterface \$con = null)
    {
        \$partial = \$this->{$collName}Partial && !\$this->isNew();
        if (null === \$this->$collName || null !== \$criteria || \$partial) {
            if (\$this->isNew() && null === \$this->$collName) {
                return 0;
            }

            if (\$partial && !\$criteria) {
                return count(\$this->get$relCol());
            }

            \$query = $fkQueryClassName::create(null, \$criteria);
            if (\$distinct) {
                \$query->distinct();
            }

            return \$query
                ->filterBy" . $this->getFKPhpNameAffix($refFK) . "(\$this)
                ->count(\$con);
        }

        return count(\$this->$collName);
    }
";
    }

    /**
     * Adds the method that returns the referrer fkey collection.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKGet(&$script, ForeignKey $refFK)
    {
        $fkQueryClassName = $this->getClassNameFromBuilder($this->getNewStubQueryBuilder($refFK->getTable()));
        $relCol = $this->getRefFKPhpNameAffix($refFK, $plural = true);
        $collName = $this->getRefFKCollVarName($refFK);

        $joinedTableObjectBuilder = $this->getNewObjectBuilder($refFK->getTable());
        $className = $joinedTableObjectBuilder->getObjectClassName();

        $script .= "
    /**
     * Gets an array of $className objects which contain a foreign key that references this object.
     *
     * If the \$criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without \$criteria, the cached collection is returned.
     * If this ".$this->getObjectClassName()." is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria \$criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface \$con optional connection object
     * @return Collection|{$className}[] List of $className objects
     * @throws PropelException
     */
    public function get$relCol(\$criteria = null, ConnectionInterface \$con = null)
    {
        \$partial = \$this->{$collName}Partial && !\$this->isNew();
        if (null === \$this->$collName || null !== \$criteria  || \$partial) {
            if (\$this->isNew() && null === \$this->$collName) {
                // return empty collection
                \$this->init" . $this->getRefFKPhpNameAffix($refFK, $plural = true) . "();
            } else {
                \$$collName = $fkQueryClassName::create(null, \$criteria)
                    ->filterBy" . $this->getFKPhpNameAffix($refFK) . "(\$this)
                    ->find(\$con);

                if (null !== \$criteria) {
                    if (false !== \$this->{$collName}Partial && count(\$$collName)) {
                        \$this->init" . $this->getRefFKPhpNameAffix($refFK, $plural = true) . "(false);

                        foreach (\$$collName as \$obj) {
                            if (false == \$this->{$collName}->contains(\$obj)) {
                                \$this->{$collName}->append(\$obj);
                            }
                        }

                        \$this->{$collName}Partial = true;
                    }

                    \$$collName" . "->getInternalIterator()->rewind();

                    return \$$collName;
                }

                if (\$partial && \$this->$collName) {
                    foreach (\$this->$collName as \$obj) {
                        if (\$obj->isNew()) {
                            \${$collName}[] = \$obj;
                        }
                    }
                }

                \$this->$collName = \$$collName;
                \$this->{$collName}Partial = false;
            }
        }

        return \$this->$collName;
    }
";
    } // addRefererGet()

    protected function addRefFKSet(&$script, ForeignKey $refFK)
    {
        $relatedName = $this->getRefFKPhpNameAffix($refFK, true);
        $relatedObjectClassName = $this->getRefFKPhpNameAffix($refFK, false);

        $inputCollection = lcfirst($relatedName);
        $inputCollectionEntry = lcfirst($this->getRefFKPhpNameAffix($refFK, false));

        $collName = $this->getRefFKCollVarName($refFK);
        $relCol   = $this->getFKPhpNameAffix($refFK, $plural = false);

        $script .= "
    /**
     * Sets a collection of $relatedObjectClassName objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection \${$inputCollection} A Propel collection.
     * @param      ConnectionInterface \$con Optional connection object
     * @return   ".$this->getObjectClassname()." The current object (for fluent API support)
     */
    public function set{$relatedName}(Collection \${$inputCollection}, ConnectionInterface \$con = null)
    {
        \${$inputCollection}ToDelete = \$this->get{$relatedName}(new Criteria(), \$con)->diff(\${$inputCollection});

        ";

        if ($refFK->isAtLeastOneLocalPrimaryKey()) {
            $script .= "
        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        \$this->{$inputCollection}ScheduledForDeletion = clone \${$inputCollection}ToDelete;
";
        } else {
            $script .= "
        \$this->{$inputCollection}ScheduledForDeletion = \${$inputCollection}ToDelete;
";
        }

        $script .= "
        foreach (\${$inputCollection}ToDelete as \${$inputCollectionEntry}Removed) {
            \${$inputCollectionEntry}Removed->set{$relCol}(null);
        }

        \$this->{$collName} = null;
        foreach (\${$inputCollection} as \${$inputCollectionEntry}) {
            \$this->add{$relatedObjectClassName}(\${$inputCollectionEntry});
        }

        \$this->{$collName} = \${$inputCollection};
        \$this->{$collName}Partial = false;

        return \$this;
    }
";
    }

    /**
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKDoAdd(&$script, $refFK)
    {
        $relatedObjectClassName = $this->getRefFKPhpNameAffix($refFK, false);
        $lowerRelatedObjectClassName = lcfirst($relatedObjectClassName);
        $collName = $this->getRefFKCollVarName($refFK);

        $script .= "
    /**
     * @param {$relatedObjectClassName} \${$lowerRelatedObjectClassName} The $lowerRelatedObjectClassName object to add.
     */
    protected function doAdd{$relatedObjectClassName}(\${$lowerRelatedObjectClassName})
    {
        \$this->{$collName}[]= \${$lowerRelatedObjectClassName};
        \${$lowerRelatedObjectClassName}->set" . $this->getFKPhpNameAffix($refFK, $plural = false) . "(\$this);
    }
";
    }

    /**
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKRemove(&$script, $refFK)
    {
        $relatedName                 = $this->getRefFKPhpNameAffix($refFK, $plural = true);
        $relatedObjectClassName      = $this->getRefFKPhpNameAffix($refFK, $plural = false);
        $inputCollection             = lcfirst($relatedName . 'ScheduledForDeletion');
        $lowerRelatedObjectClassName = lcfirst($relatedObjectClassName);

        $collName    = $this->getRefFKCollVarName($refFK);
        $relCol      = $this->getFKPhpNameAffix($refFK, $plural = false);
        $localColumn = $refFK->getLocalColumn();

        $script .= "
    /**
     * @param  {$relatedObjectClassName} \${$lowerRelatedObjectClassName} The $lowerRelatedObjectClassName object to remove.
     * @return ". $this->getObjectClassname() ." The current object (for fluent API support)
     */
    public function remove{$relatedObjectClassName}(\${$lowerRelatedObjectClassName})
    {
        if (\$this->get{$relatedName}()->contains(\${$lowerRelatedObjectClassName})) {
            \$this->{$collName}->remove(\$this->{$collName}->search(\${$lowerRelatedObjectClassName}));
            if (null === \$this->{$inputCollection}) {
                \$this->{$inputCollection} = clone \$this->{$collName};
                \$this->{$inputCollection}->clear();
            }";

        if (!$refFK->isComposite() && !$localColumn->isNotNull()) {
            $script .= "
            \$this->{$inputCollection}[]= \${$lowerRelatedObjectClassName};";
        } else {
            $script .= "
            \$this->{$inputCollection}[]= clone \${$lowerRelatedObjectClassName};";
        }

        $script .= "
            \${$lowerRelatedObjectClassName}->set{$relCol}(null);
        }

        return \$this;
    }
";
    }

    /**
     * Adds the method that gets a one-to-one related referrer fkey.
     * This is for one-to-one relationship special case.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addPKRefFKGet(&$script, ForeignKey $refFK)
    {
        $joinedTableObjectBuilder = $this->getNewObjectBuilder($refFK->getTable());
        $className = $joinedTableObjectBuilder->getObjectClassName();

        $queryClassName = $this->getClassNameFromBuilder($this->getNewStubQueryBuilder($refFK->getTable()));

        $varName = $this->getPKRefFKVarName($refFK);

        $script .= "
    /**
     * Gets a single $className object, which is related to this object by a one-to-one relationship.
     *
     * @param      ConnectionInterface \$con optional connection object
     * @return                 $className
     * @throws PropelException
     */
    public function get".$this->getRefFKPhpNameAffix($refFK, false)."(ConnectionInterface \$con = null)
    {
";
        $script .= "
        if (\$this->$varName === null && !\$this->isNew()) {
            \$this->$varName = $queryClassName::create()->findPk(\$this->getPrimaryKey(), \$con);
        }

        return \$this->$varName;
    }
";
    }

    /**
     * Adds the method that sets a one-to-one related referrer fkey.
     * This is for one-to-one relationships special case.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK   The referencing foreign key.
     */
    protected function addPKRefFKSet(&$script, ForeignKey $refFK)
    {
        $joinedTableObjectBuilder = $this->getNewObjectBuilder($refFK->getTable());
        $className = $joinedTableObjectBuilder->getObjectClassName();

        $varName = $this->getPKRefFKVarName($refFK);

        $script .= "
    /**
     * Sets a single $className object as related to this object by a one-to-one relationship.
     *
     * @param                  $className \$v $className
     * @return                 ".$this->getObjectClassName(true)." The current object (for fluent API support)
     * @throws PropelException
     */
    public function set".$this->getRefFKPhpNameAffix($refFK, false)."($className \$v = null)
    {
        \$this->$varName = \$v;

        // Make sure that that the passed-in $className isn't already associated with this object
        if (\$v !== null && \$v->get" . $this->getFKPhpNameAffix($refFK, $plural = false) . "(null, false) === null) {
            \$v->set" . $this->getFKPhpNameAffix($refFK, $plural = false) . "(\$this);
        }

        return \$this;
    }
";
    }

    protected function addCrossFKAttributes(&$script, ForeignKey $crossFK)
    {
        $joinedTableObjectBuilder = $this->getNewObjectBuilder($crossFK->getForeignTable());
        $className = $joinedTableObjectBuilder->getObjectClassName();
        $script .= "
    /**
     * @var        {$className}[] Collection to store aggregation of $className objects.
     */
    protected $" . $this->getCrossFKVarName($crossFK) . ";
";
    }

    protected function addScheduledForDeletionAttribute(&$script, $fkName)
    {
        $fkName = lcfirst($fkName);

        $script .= "
    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected \${$fkName}ScheduledForDeletion = null;
";
    }

    protected function addCrossFkScheduledForDeletion(&$script, ForeignKey $refFK, ForeignKey $crossFK)
    {
        $queryClassName         = $this->getRefFKPhpNameAffix($refFK, false) . 'Query';
        $relatedName            = $this->getFKPhpNameAffix($crossFK, true);
        $lowerRelatedName       = lcfirst($relatedName);
        $lowerSingleRelatedName = lcfirst($this->getFKPhpNameAffix($crossFK, false));

        $middelFks = $refFK->getTable()->getForeignKeys();
        $isFirstPk = ($middelFks[0]->getForeignTableCommonName() == $this->getTable()->getCommonName());

        $script .= "
            if (\$this->{$lowerRelatedName}ScheduledForDeletion !== null) {
                if (!\$this->{$lowerRelatedName}ScheduledForDeletion->isEmpty()) {
                    \$pks = array();
                    \$pk  = \$this->getPrimaryKey();
                    foreach (\$this->{$lowerRelatedName}ScheduledForDeletion->getPrimaryKeys(false) as \$remotePk) {";

        if ($isFirstPk) {
            $script .= "
                        \$pks[] = array(\$pk, \$remotePk);";
        } else {
            $script .= "
                        \$pks[] = array(\$remotePk, \$pk);";
        }

        $script .= "
                    }

                    $queryClassName::create()
                        ->filterByPrimaryKeys(\$pks)
                        ->delete(\$con);
                    \$this->{$lowerRelatedName}ScheduledForDeletion = null;
                }

                foreach (\$this->get{$relatedName}() as \${$lowerSingleRelatedName}) {
                    if (\${$lowerSingleRelatedName}->isModified()) {
                        \${$lowerSingleRelatedName}->save(\$con);
                    }
                }
            } elseif (\$this->coll{$relatedName}) {
                foreach (\$this->coll{$relatedName} as \${$lowerSingleRelatedName}) {
                    if (\${$lowerSingleRelatedName}->isModified()) {
                        \${$lowerSingleRelatedName}->save(\$con);
                    }
                }
            }
";
    }

    protected function addRefFkScheduledForDeletion(&$script, ForeignKey $refFK)
    {
        $relatedName            = $this->getRefFKPhpNameAffix($refFK, $plural = true);
        $lowerRelatedName       = lcfirst($relatedName);
        $lowerSingleRelatedName = lcfirst($this->getRefFKPhpNameAffix($refFK, $plural = false));
        $queryClassName         = $this->getNewStubQueryBuilder($refFK->getTable())->getClassname();

        $script .= "
            if (\$this->{$lowerRelatedName}ScheduledForDeletion !== null) {
                if (!\$this->{$lowerRelatedName}ScheduledForDeletion->isEmpty()) {";

            if ($refFK->isLocalColumnsRequired() || ForeignKey::CASCADE === $refFK->getOnDelete()) {
                $script .= "
                    $queryClassName::create()
                        ->filterByPrimaryKeys(\$this->{$lowerRelatedName}ScheduledForDeletion->getPrimaryKeys(false))
                        ->delete(\$con);";
            } else {
                $script .= "
                    foreach (\$this->{$lowerRelatedName}ScheduledForDeletion as \${$lowerSingleRelatedName}) {
                        // need to save related object because we set the relation to null
                        \${$lowerSingleRelatedName}->save(\$con);
                    }";
            }

            $script .= "
                    \$this->{$lowerRelatedName}ScheduledForDeletion = null;
                }
            }
";
    }

    protected function getCrossFKVarName(ForeignKey $crossFK)
    {
        return 'coll' . $this->getFKPhpNameAffix($crossFK, true);
    }

    protected function addCrossFKMethods(&$script)
    {
        foreach ($this->getTable()->getCrossFks() as $fkList) {
            list($refFK, $crossFK) = $fkList;
            $this->declareClassFromBuilder($this->getNewStubObjectBuilder($crossFK->getForeignTable()), 'Child');
            $this->declareClassFromBuilder($this->getNewStubQueryBuilder($crossFK->getForeignTable()));

            $this->addCrossFKClear($script, $crossFK);
            $this->addCrossFKInit($script, $crossFK);
            $this->addCrossFKGet($script, $refFK, $crossFK);
            $this->addCrossFKSet($script, $refFK, $crossFK);
            $this->addCrossFKCount($script, $refFK, $crossFK);
            $this->addCrossFKAdd($script, $refFK, $crossFK);
            $this->addCrossFKDoAdd($script, $refFK, $crossFK);
            $this->addCrossFKRemove($script, $refFK, $crossFK);
        }
    }

    /**
     * Adds the method that clears the referrer fkey collection.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $crossFK
     */
    protected function addCrossFKClear(&$script, ForeignKey $crossFK)
    {
        $relCol   = $this->getFKPhpNameAffix($crossFK, true);
        $collName = $this->getCrossFKVarName($crossFK);

        $script .= "
    /**
     * Clears out the {$collName} collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        add{$relCol}()
     */
    public function clear{$relCol}()
    {
        \$this->$collName = null; // important to set this to NULL since that means it is uninitialized
        \$this->{$collName}Partial = null;
    }
";
    } // addRefererClear()

    /**
     * Adds the method that clears the referrer fkey collection.
     *
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     */
    protected function addRefFKPartial(&$script, ForeignKey $refFK)
    {
        $relCol   = $this->getRefFKPhpNameAffix($refFK, $plural = true);
        $collName = $this->getRefFKCollVarName($refFK);

        $script .= "
    /**
     * Reset is the $collName collection loaded partially.
     */
    public function resetPartial{$relCol}(\$v = true)
    {
        \$this->{$collName}Partial = \$v;
    }
";
    } // addRefFKPartial()

    /**
     * Adds the method that initializes the referrer fkey collection.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $crossFK
     */
    protected function addCrossFKInit(&$script, ForeignKey $crossFK)
    {
        $relCol = $this->getFKPhpNameAffix($crossFK, true);
        $collName = $this->getCrossFKVarName($crossFK);
        $relatedObjectClassName = $this->getClassNameFromBuilder($this->getNewStubObjectBuilder($crossFK->getForeignTable()), true);

        $script .= "
    /**
     * Initializes the $collName collection.
     *
     * By default this just sets the $collName collection to an empty collection (like clear$relCol());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function init$relCol()
    {
        \$this->$collName = new ObjectCollection();
        \$this->{$collName}->setModel('$relatedObjectClassName');
    }
";
    }

    protected function addCrossFKGet(&$script, $refFK, $crossFK)
    {
        $relatedName = $this->getFKPhpNameAffix($crossFK, $plural = true);
        $relatedObjectClassName = $this->getClassNameFromBuilder($this->getNewStubObjectBuilder($crossFK->getForeignTable()));
        $selfRelationName = $this->getFKPhpNameAffix($refFK, $plural = false);
        $relatedQueryClassName = $this->getClassNameFromBuilder($this->getNewStubQueryBuilder($crossFK->getForeignTable()));
        $crossRefTableName = $crossFK->getTableName();
        $collName = $this->getCrossFKVarName($crossFK);

        $script .= "
    /**
     * Gets a collection of $relatedObjectClassName objects related by a many-to-many relationship
     * to the current object by way of the $crossRefTableName cross-reference table.
     *
     * If the \$criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without \$criteria, the cached collection is returned.
     * If this ".$this->getObjectClassName()." is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria \$criteria Optional query object to filter the query
     * @param      ConnectionInterface \$con Optional connection object
     *
     * @return ObjectCollection|{$relatedObjectClassName}[] List of {$relatedObjectClassName} objects
     */
    public function get{$relatedName}(\$criteria = null, ConnectionInterface \$con = null)
    {
        if (null === \$this->$collName || null !== \$criteria) {
            if (\$this->isNew() && null === \$this->$collName) {
                // return empty collection
                \$this->init{$relatedName}();
            } else {
                \$$collName = $relatedQueryClassName::create(null, \$criteria)
                    ->filterBy{$selfRelationName}(\$this)
                    ->find(\$con);
                if (null !== \$criteria) {
                    return \$$collName;
                }
                \$this->$collName = \$$collName;
            }
        }

        return \$this->$collName;
    }
";
    }

    protected function addCrossFKSet(&$script, $refFK, $crossFK)
    {
        $relatedNamePlural       = $this->getFKPhpNameAffix($crossFK, true);
        $relatedName             = $this->getFKPhpNameAffix($crossFK, false);
        $relatedObjectClassName  = $this->getNewStubObjectBuilder($crossFK->getForeignTable())->getUnqualifiedClassName();
        $crossRefTableName       = $crossFK->getTableName();
        $collName                = $this->getCrossFKVarName($crossFK);
        $inputCollection         = lcfirst($relatedNamePlural);
        $inputCollectionEntry    = lcfirst($this->getFKPhpNameAffix($crossFK, false));

        $script .= "
    /**
     * Sets a collection of $relatedObjectClassName objects related by a many-to-many relationship
     * to the current object by way of the $crossRefTableName cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection \${$inputCollection} A Propel collection.
     * @param  ConnectionInterface \$con Optional connection object
     * @return " . $this->getObjectClassname() . " The current object (for fluent API support)
     */
    public function set{$relatedNamePlural}(Collection \${$inputCollection}, ConnectionInterface \$con = null)
    {
        \$this->clear{$relatedNamePlural}();
        \$current{$relatedNamePlural} = \$this->get{$relatedNamePlural}();

        \$this->{$inputCollection}ScheduledForDeletion = \$current{$relatedNamePlural}->diff(\${$inputCollection});

        foreach (\${$inputCollection} as \${$inputCollectionEntry}) {
            if (!\$current{$relatedNamePlural}->contains(\${$inputCollectionEntry})) {
                \$this->doAdd{$relatedName}(\${$inputCollectionEntry});
            }
        }

        \$this->$collName = \${$inputCollection};

        return \$this;
    }
";
    }

    protected function addCrossFKCount(&$script, $refFK, $crossFK)
    {
        $relatedName = $this->getFKPhpNameAffix($crossFK, $plural = true);
        $relatedObjectClassName = $this->getClassNameFromBuilder($this->getNewStubObjectBuilder($crossFK->getForeignTable()));
        $selfRelationName = $this->getFKPhpNameAffix($refFK, $plural = false);
        $relatedQueryClassName = $this->getClassNameFromBuilder($this->getNewStubQueryBuilder($crossFK->getForeignTable()));
        $crossRefTableName = $refFK->getTableName();
        $collName = $this->getCrossFKVarName($crossFK);

        $script .= "
    /**
     * Gets the number of $relatedObjectClassName objects related by a many-to-many relationship
     * to the current object by way of the $crossRefTableName cross-reference table.
     *
     * @param      Criteria \$criteria Optional query object to filter the query
     * @param      boolean \$distinct Set to true to force count distinct
     * @param      ConnectionInterface \$con Optional connection object
     *
     * @return int the number of related $relatedObjectClassName objects
     */
    public function count{$relatedName}(\$criteria = null, \$distinct = false, ConnectionInterface \$con = null)
    {
        if (null === \$this->$collName || null !== \$criteria) {
            if (\$this->isNew() && null === \$this->$collName) {
                return 0;
            } else {
                \$query = $relatedQueryClassName::create(null, \$criteria);
                if (\$distinct) {
                    \$query->distinct();
                }

                return \$query
                    ->filterBy{$selfRelationName}(\$this)
                    ->count(\$con);
            }
        } else {
            return count(\$this->$collName);
        }
    }
";
    }

    /**
     * Adds the method that adds an object into the referrer fkey collection.
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     * @param ForeignKey $crossFK
     */
    protected function addCrossFKAdd(&$script, ForeignKey $refFK, ForeignKey $crossFK)
    {
        $relCol = $this->getFKPhpNameAffix($crossFK, true);
        $collName = $this->getCrossFKVarName($crossFK);

        $tblFK = $refFK->getTable();

        $joinedTableObjectBuilder = $this->getNewObjectBuilder($refFK->getTable());
        $className = $joinedTableObjectBuilder->getObjectClassName();

        $crossObjectName = '$' . $crossFK->getForeignTable()->getStudlyPhpName();
        $crossObjectClassName = $this->getNewObjectBuilder($crossFK->getForeignTable())->getObjectClassName();

        $relatedObjectClassName = $this->getFKPhpNameAffix($crossFK, false);

        $script .= "
    /**
     * Associate a " . $crossObjectClassName . " object to this object
     * through the " . $tblFK->getName() . " cross reference table.
     *
     * @param  " . $crossObjectClassName . " " . $crossObjectName . " The $className object to relate
     * @return "   . $this->getObjectClassname() . " The current object (for fluent API support)
     */
    public function add{$relatedObjectClassName}($crossObjectClassName $crossObjectName)
    {
        if (\$this->" . $collName . " === null) {
            \$this->init" . $relCol . "();
        }

        if (!\$this->" . $collName . "->contains(" . $crossObjectName . ")) { // only add it if the **same** object is not already associated
            \$this->doAdd{$relatedObjectClassName}($crossObjectName);
            \$this->" . $collName . "[] = " . $crossObjectName . ";
        }

        return \$this;
    }
";
    }

    /**
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     * @param ForeignKey $crossFK
     */
    protected function addCrossFKDoAdd(&$script, ForeignKey $refFK, ForeignKey $crossFK)
    {
         $relatedObjectClassName      = $this->getFKPhpNameAffix($crossFK, $plural = false);
         $selfRelationNamePlural      = $this->getFKPhpNameAffix($refFK, $plural = true);
         $lowerRelatedObjectClassName = lcfirst($relatedObjectClassName);
         $joinedTableObjectBuilder    = $this->getNewObjectBuilder($refFK->getTable());
         $className                   = $joinedTableObjectBuilder->getObjectClassname();
         $refKObjectClassName         = $this->getRefFKPhpNameAffix($refFK, $plural = false);
         $tblFK                       = $refFK->getTable();
         $foreignObjectName           = '$' . $tblFK->getStudlyPhpName();

        $script .= "
    /**
     * @param    {$relatedObjectClassName} \${$lowerRelatedObjectClassName} The $lowerRelatedObjectClassName object to add.
     */
    protected function doAdd{$relatedObjectClassName}(\${$lowerRelatedObjectClassName})
    {
        {$foreignObjectName} = new {$className}();
        {$foreignObjectName}->set{$relatedObjectClassName}(\${$lowerRelatedObjectClassName});
        \$this->add{$refKObjectClassName}({$foreignObjectName});
        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!\${$lowerRelatedObjectClassName}->get{$selfRelationNamePlural}()->contains(\$this)) {
            \$foreignCollection   = \${$lowerRelatedObjectClassName}->get{$selfRelationNamePlural}();
            \$foreignCollection[] = \$this;
        }
    }
";
    }

    /**
     * Adds the method that remove an object from the referrer fkey collection.
     * @param string     $script  The script will be modified in this method.
     * @param ForeignKey $refFK
     * @param ForeignKey $crossFK
     */
    protected function addCrossFKRemove(&$script, ForeignKey $refFK, ForeignKey $crossFK)
    {
        $relCol   = $this->getFKPhpNameAffix($crossFK, $plural = true);
        $collName = 'coll' . $relCol;
        $tblFK    = $refFK->getTable();

        $joinedTableObjectBuilder = $this->getNewObjectBuilder($refFK->getTable());
        $className                = $joinedTableObjectBuilder->getObjectClassname();
        $M2MScheduledForDeletion  = lcfirst($relCol) . "ScheduledForDeletion";
        $crossObjectName          = '$' . $crossFK->getForeignTable()->getStudlyPhpName();
        $crossObjectClassName     = $this->getNewObjectBuilder($crossFK->getForeignTable())->getObjectClassname();
        $relatedObjectClassName   = $this->getFKPhpNameAffix($crossFK, $plural = false);

        $script .= "
    /**
     * Remove a {$crossObjectClassName} object to this object
     * through the {$tblFK->getName()} cross reference table.
     *
     * @param {$crossObjectClassName} {$crossObjectName} The $className object to relate
     * @return " . $this->getObjectClassname() . " The current object (for fluent API support)
     */
    public function remove{$relatedObjectClassName}($crossObjectClassName $crossObjectName)
    {
        if (\$this->get{$relCol}()->contains({$crossObjectName})) {
            \$this->{$collName}->remove(\$this->{$collName}->search({$crossObjectName}));

            if (null === \$this->{$M2MScheduledForDeletion}) {
                \$this->{$M2MScheduledForDeletion} = clone \$this->{$collName};
                \$this->{$M2MScheduledForDeletion}->clear();
            }

            \$this->{$M2MScheduledForDeletion}[] = {$crossObjectName};
        }

        return \$this;
    }
";
    }

    /**
     * Adds the workhourse doSave() method.
     * @param string &$script The script will be modified in this method.
     */
    protected function addDoSave(&$script)
    {
        $table = $this->getTable();

        $reloadOnUpdate = $table->isReloadOnUpdate();
        $reloadOnInsert = $table->isReloadOnInsert();

        $script .= "
    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface \$con";
        if ($reloadOnUpdate || $reloadOnInsert) {
            $script .= "
     * @param      boolean \$skipReload Whether to skip the reload for this object from database.";
        }
        $script .= "
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface \$con".($reloadOnUpdate || $reloadOnInsert ? ", \$skipReload = false" : "").")
    {
        \$affectedRows = 0; // initialize var to track total num of affected rows
        if (!\$this->alreadyInSave) {
            \$this->alreadyInSave = true;
";
        if ($reloadOnInsert || $reloadOnUpdate) {
            $script .= "
            \$reloadObject = false;
";
        }

        if (count($table->getForeignKeys())) {

            $script .= "
            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.
";

            foreach ($table->getForeignKeys() as $fk) {
                $aVarName = $this->getFKVarName($fk);
                $script .= "
            if (\$this->$aVarName !== null) {
                if (\$this->" . $aVarName . "->isModified() || \$this->" . $aVarName . "->isNew()) {
                    \$affectedRows += \$this->" . $aVarName . "->save(\$con);
                }
                \$this->set".$this->getFKPhpNameAffix($fk, false)."(\$this->$aVarName);
            }
";
            } // foreach foreign k
        } // if (count(foreign keys))

        $script .= "
            if (\$this->isNew() || \$this->isModified()) {
                // persist changes
                if (\$this->isNew()) {
                    \$this->doInsert(\$con);";
        if ($reloadOnInsert) {
            $script .= "
                    if (!\$skipReload) {
                        \$reloadObject = true;
                    }";
        }
        $script .= "
                } else {
                    \$this->doUpdate(\$con);";
        if ($reloadOnUpdate) {
            $script .= "
                    if (!\$skipReload) {
                        \$reloadObject = true;
                    }";
        }
        $script .= "
                }
                \$affectedRows += 1;";

        // We need to rewind any LOB columns
        foreach ($table->getColumns() as $col) {
            $clo = $col->getLowercasedName();
            if ($col->isLobType()) {
                $script .= "
                // Rewind the $clo LOB column, since PDO does not rewind after inserting value.
                if (\$this->$clo !== null && is_resource(\$this->$clo)) {
                    rewind(\$this->$clo);
                }
";
            }
        }

        $script .= "
                \$this->resetModified();
            }
";

        if ($table->hasCrossForeignKeys()) {
            foreach ($table->getCrossFks() as $fkList) {
                list($refFK, $crossFK) = $fkList;
                $this->addCrossFkScheduledForDeletion($script, $refFK, $crossFK);
            }
        }

        foreach ($table->getReferrers() as $refFK) {
            if ($refFK->isLocalPrimaryKey()) {
                $varName = $this->getPKRefFKVarName($refFK);
                $script .= "
            if (\$this->$varName !== null) {
                if (!\$this->{$varName}->isDeleted() && (\$this->{$varName}->isNew() || \$this->{$varName}->isModified())) {
                    \$affectedRows += \$this->{$varName}->save(\$con);
                }
            }
";
            } else {
                $this->addRefFkScheduledForDeletion($script, $refFK);

                $collName = $this->getRefFKCollVarName($refFK);
                $script .= "
                if (\$this->$collName !== null) {
            foreach (\$this->$collName as \$referrerFK) {
                    if (!\$referrerFK->isDeleted() && (\$referrerFK->isNew() || \$referrerFK->isModified())) {
                        \$affectedRows += \$referrerFK->save(\$con);
                    }
                }
            }
";
            } // if refFK->isLocalPrimaryKey()
        } /* foreach getReferrers() */

        $script .= "
            \$this->alreadyInSave = false;
";
        if ($reloadOnInsert || $reloadOnUpdate) {
            $script .= "
            if (\$reloadObject) {
                \$this->reload(\$con);
            }
";
        }
        $script .= "
        }

        return \$affectedRows;
    } // doSave()
";

    }

    /**
     * get the doInsert() method code
     *
     * @return string the doInsert() method code
     */
    protected function addDoInsert()
    {
        $table = $this->getTable();
        $script = "
    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface \$con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface \$con)
    {";
        if ($this->getPlatform() instanceof MssqlPlatform) {
            if ($table->hasAutoIncrementPrimaryKey() ) {
                $script .= "
        \$this->modifiedColumns[] = " . $this->getColumnConstant($table->getAutoIncrementPrimaryKey()).';';
            }
            $script .= "
        \$criteria = \$this->buildCriteria();";
            if ($this->getTable()->getIdMethod() != IdMethod::NO_ID_METHOD) {
                $script .= $this->addDoInsertBodyWithIdMethod();
            } else {
                $script .= $this->addDoInsertBodyStandard();
            }
        } else {
            $script .= $this->addDoInsertBodyRaw();
        }
            $script .= "
        \$this->setNew(false);
    }
";

        return $script;
    }

    protected function addDoInsertBodyStandard()
    {
        return "
        \$pk = \$criteria->insert(\$con);";
    }

    protected function addDoInsertBodyWithIdMethod()
    {
        $table = $this->getTable();
        $script = '';
        foreach ($table->getPrimaryKey() as $col) {
            if (!$col->isAutoIncrement()) {
                continue;
            }
            $colConst = $this->getColumnConstant($col);
            if (!$table->isAllowPkInsert()) {
                $script .= "
        if (\$criteria->keyContainsValue($colConst) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . $colConst . ')');
        }";
                if (!$this->getPlatform()->supportsInsertNullPk()) {
                    $script .= "
        // remove pkey col since this table uses auto-increment and passing a null value for it is not valid
        \$criteria->remove($colConst);";
                }
            } elseif (!$this->getPlatform()->supportsInsertNullPk()) {
                $script .= "
        // remove pkey col if it is null since this table does not accept that
        if (\$criteria->containsKey($colConst) && !\$criteria->keyContainsValue($colConst) ) {
            \$criteria->remove($colConst);
        }";
            }
        }

        $script .= $this->addDoInsertBodyStandard();

        foreach ($table->getPrimaryKey() as $col) {
            if (!$col->isAutoIncrement()) {
                continue;
            }
            if ($table->isAllowPkInsert()) {
                $script .= "
        if (\$pk !== null) {
            \$this->set".$col->getPhpName()."(\$pk);  //[IMV] update autoincrement primary key
        }";
            } else {
                $script .= "
        \$this->set".$col->getPhpName()."(\$pk);  //[IMV] update autoincrement primary key";
            }
        }

        return $script;
    }

    /**
     * Boosts ActiveRecord::doInsert() by doing more calculations at buildtime.
     */
    protected function addDoInsertBodyRaw()
    {
        $this->declareClasses(
            '\Propel\Runtime\Propel',
            '\PDO'
        );
        $table = $this->getTable();
        $platform = $this->getPlatform();
        $primaryKeyMethodInfo = '';
        if ($table->getIdMethodParameters()) {
            $params = $table->getIdMethodParameters();
            $imp = $params[0];
            $primaryKeyMethodInfo = $imp->getValue();
        } elseif ($table->getIdMethod() == IdMethod::NATIVE && ($platform->getNativeIdMethod() == PlatformInterface::SEQUENCE || $platform->getNativeIdMethod() == PlatformInterface::SERIAL)) {
            $primaryKeyMethodInfo = $platform->getSequenceName($table);
        }
        $query = 'INSERT INTO ' . $platform->quoteIdentifier($table->getName()) . ' (%s) VALUES (%s)';
        $script = "
        \$modifiedColumns = array();
        \$index = 0;
";

        foreach ($table->getPrimaryKey() as $column) {
            if (!$column->isAutoIncrement()) {
                continue;
            }
            $constantName = $this->getColumnConstant($column);
            if ($platform->supportsInsertNullPk()) {
                $script .= "
        \$this->modifiedColumns[] = $constantName;";
            }
            $columnProperty = $column->getLowercasedName();
            if (!$table->isAllowPkInsert()) {
                $script .= "
        if (null !== \$this->{$columnProperty}) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . $constantName . ')');
        }";
            } elseif (!$platform->supportsInsertNullPk()) {
                $script .= "
        // add primary key column only if it is not null since this database does not accept that
        if (null !== \$this->{$columnProperty}) {
            \$this->modifiedColumns[] = $constantName;
        }";
            }
        }

        // if non auto-increment but using sequence, get the id first
        if (!$platform->isNativeIdMethodAutoIncrement() && $table->getIdMethod() == "native") {
            $column = $table->getFirstPrimaryKeyColumn();
            $columnProperty = $column->getLowercasedName();
            $script .= "
        if (null === \$this->{$columnProperty}) {
            try {";
            $script .= $platform->getIdentifierPhp('$this->'. $columnProperty, '$con', $primaryKeyMethodInfo, '                ');
            $script .= "
            } catch (Exception \$e) {
                throw new PropelException('Unable to get sequence id.', 0, \$e);
            }
        }
";
        }

        $script .= "

         // check the columns in natural order for more readable SQL queries";
        foreach ($table->getColumns() as $column) {
            $constantName = $this->getColumnConstant($column);
            $identifier = var_export($platform->quoteIdentifier(strtoupper($column->getName())), true);
            $script .= "
        if (\$this->isColumnModified($constantName)) {
            \$modifiedColumns[':p' . \$index++]  = $identifier;
        }";
        }

        $script .= "

        \$sql = sprintf(
            '$query',
            implode(', ', \$modifiedColumns),
            implode(', ', array_keys(\$modifiedColumns))
        );

        try {
            \$stmt = \$con->prepare(\$sql);
            foreach (\$modifiedColumns as \$identifier => \$columnName) {
                switch (\$columnName) {";
        foreach ($table->getColumns() as $column) {
            $columnNameCase = var_export($platform->quoteIdentifier(strtoupper($column->getName())), true);
            $script .= "
                    case $columnNameCase:";
            $script .= $platform->getColumnBindingPHP($column, "\$identifier", '$this->' . $column->getLowercasedName(), '                        ');
            $script .= "
                        break;";
        }
        $script .= "
                }
            }
            \$stmt->execute();
        } catch (Exception \$e) {
            Propel::log(\$e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', \$sql), 0, \$e);
        }
";

        // if auto-increment, get the id after
        if ($platform->isNativeIdMethodAutoIncrement() && $table->getIdMethod() == "native") {
            $column = $table->getFirstPrimaryKeyColumn();
            $script .= "
        try {";
            $script .= $platform->getIdentifierPhp('$pk', '$con', $primaryKeyMethodInfo);
            $script .= "
        } catch (Exception \$e) {
            throw new PropelException('Unable to get autoincrement id.', 0, \$e);
        }";
            if ($table->isAllowPkInsert()) {
                $script .= "
        if (\$pk !== null) {
            \$this->set".$column->getPhpName()."(\$pk);
        }";
            } else {
                $script .= "
        \$this->set".$column->getPhpName()."(\$pk);";
            }
            $script .= "
";
        }

        return $script;
    }

    /**
     * get the doUpdate() method code
     *
     * @return string the doUpdate() method code
     */
    protected function addDoUpdate()
    {
        return "
    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface \$con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface \$con)
    {
        \$selectCriteria = \$this->buildPkeyCriteria();
        \$valuesCriteria = \$this->buildCriteria();

        return \$selectCriteria->doUpdate(\$valuesCriteria, \$con);
    }
";
    }

    /**
     * Adds the $alreadyInSave attribute, which prevents attempting to re-save the same object.
     * @param string &$script The script will be modified in this method.
     */
    protected function addAlreadyInSaveAttribute(&$script)
    {
        $script .= "
    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected \$alreadyInSave = false;
";
    }

    /**
     * Adds the save() method.
     * @param string &$script The script will be modified in this method.
     */
    protected function addSave(&$script)
    {
        $this->addSaveComment($script);
        $this->addSaveOpen($script);
        $this->addSaveBody($script);
        $this->addSaveClose($script);
    }

    /**
     * Adds the comment for the save method
     * @param string &$script The script will be modified in this method.
     * @see addSave()
     **/
    protected function addSaveComment(&$script)
    {
        $table = $this->getTable();
        $reloadOnUpdate = $table->isReloadOnUpdate();
        $reloadOnInsert = $table->isReloadOnInsert();

        $script .= "
    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.";
        if ($reloadOnUpdate) {
            $script .= "
     *
     * Since this table was configured to reload rows on update, the object will
     * be reloaded from the database if an UPDATE operation is performed (unless
     * the \$skipReload parameter is TRUE).";
        }
        if ($reloadOnInsert) {
            $script .= "
     *
     * Since this table was configured to reload rows on insert, the object will
     * be reloaded from the database if an INSERT operation is performed (unless
     * the \$skipReload parameter is TRUE).";
        }
        $script .= "
     *
     * @param      ConnectionInterface \$con";
        if ($reloadOnUpdate || $reloadOnInsert) {
            $script .= "
     * @param      boolean \$skipReload Whether to skip the reload for this object from database.";
        }
        $script .= "
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */";
    }

    /**
     * Adds the function declaration for the save method
     * @param string &$script The script will be modified in this method.
     * @see addSave()
     **/
    protected function addSaveOpen(&$script)
    {
        $table = $this->getTable();
        $reloadOnUpdate = $table->isReloadOnUpdate();
        $reloadOnInsert = $table->isReloadOnInsert();
        $script .= "
    public function save(ConnectionInterface \$con = null".($reloadOnUpdate || $reloadOnInsert ? ", \$skipReload = false" : "").")
    {";
    }

    /**
     * Adds the function body for the save method
     * @param string &$script The script will be modified in this method.
     * @see addSave()
     **/
    protected function addSaveBody(&$script)
    {
        $table = $this->getTable();
        $reloadOnUpdate = $table->isReloadOnUpdate();
        $reloadOnInsert = $table->isReloadOnInsert();

        $script .= "
        if (\$this->isDeleted()) {
            throw new PropelException(\"You cannot save an object that has been deleted.\");
        }

        if (\$con === null) {
            \$con = Propel::getServiceContainer()->getWriteConnection(".$this->getTableMapClass()."::DATABASE_NAME);
        }

        \$con->beginTransaction();
        \$isInsert = \$this->isNew();
        try {";

        if ($this->getGeneratorConfig()->getBuildProperty('addHooks')) {
            // save with runtime hooks
            $script .= "
            \$ret = \$this->preSave(\$con);";
            $this->applyBehaviorModifier('preSave', $script, "            ");
            $script .= "
            if (\$isInsert) {
                \$ret = \$ret && \$this->preInsert(\$con);";
            $this->applyBehaviorModifier('preInsert', $script, "                ");
            $script .= "
            } else {
                \$ret = \$ret && \$this->preUpdate(\$con);";
            $this->applyBehaviorModifier('preUpdate', $script, "                ");
            $script .= "
            }
            if (\$ret) {
                \$affectedRows = \$this->doSave(\$con".($reloadOnUpdate || $reloadOnInsert ? ", \$skipReload" : "").");
                if (\$isInsert) {
                    \$this->postInsert(\$con);";
            $this->applyBehaviorModifier('postInsert', $script, "                    ");
            $script .= "
                } else {
                    \$this->postUpdate(\$con);";
            $this->applyBehaviorModifier('postUpdate', $script, "                    ");
            $script .= "
                }
                \$this->postSave(\$con);";
                $this->applyBehaviorModifier('postSave', $script, "                ");
                $script .= "
                ".$this->getTableMapClassName()."::addInstanceToPool(\$this);
            } else {
                \$affectedRows = 0;
            }
            \$con->commit();

            return \$affectedRows;";
        } else {
            // save without runtime hooks
            $this->applyBehaviorModifier('preSave', $script, "            ");
            if ($this->hasBehaviorModifier('preUpdate')) {
                $script .= "
            if (!\$isInsert) {";
                $this->applyBehaviorModifier('preUpdate', $script, "                ");
                $script .= "
            }";
            }
            if ($this->hasBehaviorModifier('preInsert')) {
                $script .= "
            if (\$isInsert) {";
                $this->applyBehaviorModifier('preInsert', $script, "                ");
                $script .= "
            }";
            }
            $script .= "
            \$affectedRows = \$this->doSave(\$con".($reloadOnUpdate || $reloadOnInsert ? ", \$skipReload" : "").");";
            $this->applyBehaviorModifier('postSave', $script, "            ");
            if ($this->hasBehaviorModifier('postUpdate')) {
                $script .= "
            if (!\$isInsert) {";
                $this->applyBehaviorModifier('postUpdate', $script, "                ");
                $script .= "
            }";
            }
            if ($this->hasBehaviorModifier('postInsert')) {
                $script .= "
            if (\$isInsert) {";
                $this->applyBehaviorModifier('postInsert', $script, "                ");
                $script .= "
            }";
            }
            $script .= "
            \$con->commit();
            ".$this->getTableMapClassName()."::addInstanceToPool(\$this);

            return \$affectedRows;";
        }

        $script .= "
        } catch (Exception \$e) {
            \$con->rollBack();
            throw \$e;
        }";
    }

    /**
     * Adds the function close for the save method
     * @param string &$script The script will be modified in this method.
     * @see addSave()
     **/
    protected function addSaveClose(&$script)
    {
        $script .= "
    }
";
    }

    /**
     * Adds the ensureConsistency() method to ensure that internal state is correct.
     * @param string &$script The script will be modified in this method.
     */
    protected function addEnsureConsistency(&$script)
    {
        $table = $this->getTable();

        $script .= "
    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {";
        foreach ($table->getColumns() as $col) {

            $clo=$col->getLowercasedName();

            if ($col->isForeignKey()) {
                foreach ($col->getForeignKeys() as $fk) {

                    $tblFK = $table->getDatabase()->getTable($fk->getForeignTableName());
                    $colFK = $tblFK->getColumn($fk->getMappedForeignColumn($col->getName()));
                    $varName = $this->getFKVarName($fk);

                    $script .= "
        if (\$this->".$varName." !== null && \$this->$clo !== \$this->".$varName."->get".$colFK->getPhpName()."()) {
            \$this->$varName = null;
        }";
                } // foreach
            } /* if col is foreign key */

        } // foreach

        $script .= "
    } // ensureConsistency
";
    } // addCheckRelConsistency

    /**
     * Adds the copy() method, which (in complex OM) includes the $deepCopy param for making copies of related objects.
     * @param string &$script The script will be modified in this method.
     */
    protected function addCopy(&$script)
    {
        $this->addCopyInto($script);

        $script .= "
    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean \$deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return                 ".$this->getObjectClassName(true)." Clone of current object.
     * @throws PropelException
     */
    public function copy(\$deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        \$clazz = get_class(\$this);
        " . $this->buildObjectInstanceCreationCode('$copyObj', '$clazz') . "
        \$this->copyInto(\$copyObj, \$deepCopy);

        return \$copyObj;
    }
";
    }

    /**
     * Adds the copyInto() method, which takes an object and sets contents to match current object.
     * In complex OM this method includes the $deepCopy param for making copies of related objects.
     * @param string &$script The script will be modified in this method.
     */
    protected function addCopyInto(&$script)
    {
        $table = $this->getTable();

        $script .= "
    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object \$copyObj An object of ".$this->getObjectClassName(true)." (or compatible) type.
     * @param      boolean \$deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean \$makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto(\$copyObj, \$deepCopy = false, \$makeNew = true)
    {";

        $autoIncCols = array();
        foreach ($table->getColumns() as $col) {
            /* @var        $col Column */
            if ($col->isAutoIncrement()) {
                $autoIncCols[] = $col;
            }
        }

        foreach ($table->getColumns() as $col) {
            if (!in_array($col, $autoIncCols, true)) {
                $script .= "
        \$copyObj->set".$col->getPhpName()."(\$this->get".$col->getPhpName()."());";
            }
        } // foreach

        // Avoid useless code by checking to see if there are any referrers
        // to this table:
        if (count($table->getReferrers()) > 0) {
            $script .= "

        if (\$deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            \$copyObj->setNew(false);
";
            foreach ($table->getReferrers() as $fk) {
                //HL: commenting out self-referential check below
                //        it seems to work as expected and is probably desirable to have those referrers from same table deep-copied.
                //if ( $fk->getTable()->getName() != $table->getName() ) {

                if ($fk->isLocalPrimaryKey()) {

                    $afx = $this->getRefFKPhpNameAffix($fk, false);
                    $script .= "
            \$relObj = \$this->get$afx();
            if (\$relObj) {
                \$copyObj->set$afx(\$relObj->copy(\$deepCopy));
            }
";
                } else {

                    $script .= "
            foreach (\$this->get".$this->getRefFKPhpNameAffix($fk, true)."() as \$relObj) {
                if (\$relObj !== \$this) {  // ensure that we don't try to copy a reference to ourselves
                    \$copyObj->add".$this->getRefFKPhpNameAffix($fk)."(\$relObj->copy(\$deepCopy));
                }
            }
";
                }
                // HL: commenting out close of self-referential check
                // } /* if tblFK != table */
            } /* foreach */
            $script .= "
        } // if (\$deepCopy)
";
        } /* if (count referrers > 0 ) */

        $script .= "
        if (\$makeNew) {
            \$copyObj->setNew(true);";

        // Note: we're no longer resetting non-autoincrement primary keys to default values
        // due to: http://propel.phpdb.org/trac/ticket/618
        foreach ($autoIncCols as $col) {
                $coldefval = $col->getPhpDefaultValue();
                $coldefval = var_export($coldefval, true);
                $script .= "
            \$copyObj->set".$col->getPhpName() ."($coldefval); // this is a auto-increment column, so set to default value";
        } // foreach
        $script .= "
        }
    }
";
    } // addCopyInto()

    /**
     * Adds clear method
     * @param string &$script The script will be modified in this method.
     */
    protected function addClear(&$script)
    {
        $table = $this->getTable();

        $script .= "
    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {";
        foreach ($table->getColumns() as $col) {
            $clo = $col->getLowercasedName();
            $script .= "
        \$this->".$clo." = null;";
            if ($col->isLazyLoad()) {
                $script .= "
        \$this->".$clo."_isLoaded = false;";
            }
            if ($col->getType() == PropelTypes::OBJECT || $col->getType() == PropelTypes::PHP_ARRAY) {
                $cloUnserialized = $clo.'_unserialized';

                $script .="
        \$this->$cloUnserialized = null;";
            }
        }

        $script .= "
        \$this->alreadyInSave = false;
        \$this->clearAllReferences();";

        if ($this->hasDefaultValues()) {
            $script .= "
        \$this->applyDefaultValues();";
        }

        $script .= "
        \$this->resetModified();
        \$this->setNew(true);
        \$this->setDeleted(false);
    }
";
    }


    /**
     * Adds clearAllReferencers() method which resets all the collections of referencing
     * fk objects.
     * @param string &$script The script will be modified in this method.
     */
    protected function addClearAllReferences(&$script)
    {
        $table = $this->getTable();
        $script .= "
    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param      boolean \$deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences(\$deep = false)
    {
        if (\$deep) {";
        $vars = array();
        foreach ($this->getTable()->getReferrers() as $refFK) {
            if ($refFK->isLocalPrimaryKey()) {
                $varName = $this->getPKRefFKVarName($refFK);
                $script .= "
            if (\$this->$varName) {
                \$this->{$varName}->clearAllReferences(\$deep);
            }";
            } else {
                $varName = $this->getRefFKCollVarName($refFK);
                $script .= "
            if (\$this->$varName) {
                foreach (\$this->$varName as \$o) {
                    \$o->clearAllReferences(\$deep);
                }
            }";
            }
            $vars[] = $varName;
        }
        foreach ($this->getTable()->getCrossFks() as $fkList) {
            list($refFK, $crossFK) = $fkList;
            $varName = $this->getCrossFKVarName($crossFK);
            $script .= "
            if (\$this->$varName) {
                foreach (\$this->$varName as \$o) {
                    \$o->clearAllReferences(\$deep);
                }
            }";
            $vars[] = $varName;
        }

        $script .= "
        } // if (\$deep)
";

        $this->applyBehaviorModifier('objectClearReferences', $script, "        ");

        foreach ($vars as $varName) {
            $script .= "
        if (\$this->$varName instanceof Collection) {
            \$this->{$varName}->clearIterator();
        }
        \$this->$varName = null;";
        }

        foreach ($table->getForeignKeys() as $fk) {
            $varName = $this->getFKVarName($fk);
            $script .= "
        \$this->$varName = null;";
        }

        $script .= "
    }
";
    }

    /**
     * Adds a magic __toString() method if a string column was defined as primary string
     * @param string &$script The script will be modified in this method.
     */
    protected function addPrimaryString(&$script)
    {
        foreach ($this->getTable()->getColumns() as $column) {
            if ($column->isPrimaryString()) {
                $script .= "
    /**
     * Return the string representation of this object
     *
     * @return string The value of the '{$column->getName()}' column
     */
    public function __toString()
    {
        return (string) \$this->get{$column->getPhpName()}();
    }
";

                return;
            }
        }
        // no primary string column, falling back to default string format
        $script .= "
    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) \$this->exportTo(" . $this->getTableMapClassName() . "::DEFAULT_STRING_FORMAT);
    }
";
    }

    /**
     * Adds a magic __call() method.
     *
     * @param string &$script
     */
    protected function addMagicCall(&$script)
    {
        $behaviorCallScript = '';
        $this->applyBehaviorModifier('objectCall', $behaviorCallScript, "    ");

        $script .= $this->renderTemplate('baseObjectMethodMagicCall', array(
                'behaviorCallScript' => $behaviorCallScript
                ));
    }
}
