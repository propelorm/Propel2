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
use Propel\Generator\Model\Table;
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
     * @TODO: made public becuase use in template
     * @param  Column $column
     * @return string
     */
    public function getTemporalFormatter(Column $column)
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
     * TODO: made this public because template
     *
     * @param  Column $column
     * @throws EngineException
     * @return string
     */
    public function getDefaultValueString(Column $column)
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
                throw new EngineException(sprintf('Unable to parse default temporal value "%s" for column "%s"', $column->getDefaultValueString(), $column->getFullyQualifiedName()), 0, $exception);
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
            '\Propel\Runtime\Map\TableMap',
            '\Propel\Runtime\Util\PropelDateTime',
            '\Propel\Runtime\ActiveQuery\PropelQuery'
        );

        $script .= $this->getTwig()->render('Object/_classBody.php.twig', ['builder' => $this]);
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
     * Adds a push method for an array column.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     *
     * @TOdo: made public for twig
     */
    public function addAddArrayElement(Column $col)
    {
        $script = '';
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

        return $script;
    }

    /**
     * Adds a remove method for an array column.
     * @param string &$script The script will be modified in this method.
     * @param Column $col     The current column.
     *
     * @Todo: made public for twig
     */
    public function addRemoveArrayElement(Column $col)
    {
        $script = '';
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

        return $script;
    }

    public function getInvalidTemporalString(Column $column)
    {
        if ($this->getPlatform() instanceof MysqlPlatform) {
            if($column->getType() === PropelTypes::TIMESTAMP) {
                return '0000-00-00 00:00:00';
            } elseif($column->getType() === PropelTypes::DATE) {
                return '0000-00-00';
            }
        }

        return null;
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
     * Adds the class attributes that are needed to store fkey related objects.
     * @param string &$script The script will be modified in this method.
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
     * Adds the accessor (getter) method for getting an fkey related object.
     * @param string &$script The script will be modified in this method.
     * @param ForeignKey $fk
     */
    public function addFKAccessor(ForeignKey $fk)
    {
        $script = '';
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

        return $script;
    } // addFKAccessor

    /**
     * Adds a convenience method for setting a related object by specifying the primary key.
     * This can be used in conjunction with the getPrimaryKey() for systems where nothing is known
     * about the actual objects being related.
     * @param string &$script The script will be modified in this method.
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
     * Adds the attributes used to store objects that have referrer fkey relationships to this object.
     * <code>protected collVarName;</code>
     * <code>private lastVarNameCriteria = null;</code>
     * @param string &$script The script will be modified in this method.
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

    /**
     * @Todo: made public for use in template
     */
    public  function getCrossFKVarName(ForeignKey $crossFK)
    {
        return 'coll' . $this->getFKPhpNameAffix($crossFK, true);
    }


    /**
     * Adds the method that adds an object into the referrer fkey collection.
     * @param string &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     * @param ForeignKey $crossFK
     */
    protected function addCrossFKAdd(ForeignKey $refFK, ForeignKey $crossFK)
    {
        $script = '';
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

        return $script;
    }

    /**
     * @param string     &$script The script will be modified in this method.
     * @param ForeignKey $refFK
     * @param ForeignKey $crossFK
     */
    protected function addCrossFKDoAdd(ForeignKey $refFK, ForeignKey $crossFK)
    {
        $script = '';
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

        return $script;
    }

    /**
     * Adds the method that remove an object from the referrer fkey collection.
     * @param string $script The script will be modified in this method.
     * @param ForeignKey $refFK
     * @param ForeignKey $crossFK
     */
    protected function addCrossFKRemove(ForeignKey $refFK, ForeignKey $crossFK)
    {
        $script = '';
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

        return $script;
    }

    /**
     * Adds the workhourse doSave() method.
     * @param string &$script The script will be modified in this method.
     */
    public function addDoSave()
    {
        $script = '';
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


        return $script;
    }

    /**
     * get the doInsert() method code
     *
     * @return string the doInsert() method code
     */
    public function addDoInsert()
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

}
