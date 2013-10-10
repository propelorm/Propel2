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
    private $twig;

    public function __construct(Table $table)
    {
        parent::__construct($table);
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/templates/');
        $this->twig = new \Twig_Environment($loader, ['autoescape' => false, 'strict_variables' => true, 'cache' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'propel2-cache', 'auto_reload' => true]);
        $this->twig->addFilter('addSlashes', new \Twig_SimpleFilter('addSlashes', 'addslashes'));
        $this->twig->addFilter('lcfirst', new \Twig_SimpleFilter('lcfirst', 'lcfirst'));
        $this->twig->addFilter('ucfirst', new \Twig_SimpleFilter('ucfirst', 'ucfirst'));
        $this->twig->addFilter('indent', new \Twig_SimpleFilter('indent', function($string) {
                $lines = explode(PHP_EOL, $string);
                $output = '';

                foreach($lines as $line) {
                    $output .= '    ' . $line . PHP_EOL;
                }

                return $output;
            }));
        $this->twig->addFilter(
            'varExport',
            new \Twig_SimpleFilter('varExport', function ($input) {
                return var_export($input, true);
            })
        );

        foreach($table->getBehaviors() as $behavior) {
            $path = $behavior->getTemplateDirectory();
            if($path !== null) {
                $loader->prependPath($behavior->getTemplateDirectory(), $behavior->getTemplateNamespace());
            }
        }
    }


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
            '\Propel\Runtime\Map\TableMap',
            '\Propel\Runtime\Util\PropelDateTime'
        );

        $script .= $this->twig->render('Object/_classBody.php.twig', ['builder' => $this]);


        $this->addRefFKMethods($script);
        $this->addCrossFKMethods($script);
        $this->addClearAllReferences($script);

        // apply behaviors
        $this->applyBehaviorModifier('objectMethods', $script, "    ");
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
     * Adds the function body for the lazy loader method.
     *
     * @param string &$script
     * @param Column $column
     * TODO: made this public because of twig usage
     */
    public function addLazyLoaderBody(Column $column)
    {
        $script = '';
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

        return $script;
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
     * Adds the method that fetches fkey-related (referencing) objects but also joins in data from another table.
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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

    /**
     * @Todo: made public for use in template
     */
    public  function getCrossFKVarName(ForeignKey $crossFK)
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string &$script The script will be modified in this method.
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
     * @param string $script The script will be modified in this method.
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

    /**
     * get the doUpdate() method code
     *
     * @return string the doUpdate() method code
     */
    public function addDoUpdate()
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

}
