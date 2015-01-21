<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Platform\PlatformInterface;

/**
 * A class for holding data about a column used in an application.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Jon S. Stevens <jon@latchkey.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Bernd Goldschmidt <bgoldschmidt@rapidsoft.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Column extends MappingModel
{
    const DEFAULT_TYPE       = 'VARCHAR';
    const DEFAULT_VISIBILITY = 'public';
    const CONSTANT_PREFIX    = 'COL_';

    public static $validVisibilities = [ 'public', 'protected', 'private' ];

    private $name;
    private $description;
    private $phpName;
    private $phpSingularName;
    private $phpNamingMethod;
    private $isNotNull;
    private $namePrefix;
    private $accessorVisibility;
    private $mutatorVisibility;
    private $typeHint;

    /**
     * The name to use for the tableMap constant that identifies this column.
     * (Will be converted to all-uppercase in the templates.)
     * @var string
     */
    private $tableMapName;

    /**
     * Native PHP type (scalar or class name)
     * @var string "string", "boolean", "int", "double"
     */
    private $phpType;

    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var Table
     */
    private $parentTable;

    private $position;
    private $isPrimaryKey;
    private $isNodeKey;
    private $nodeKeySep;
    private $isNestedSetLeftKey;
    private $isNestedSetRightKey;
    private $isTreeScopeKey;
    private $isUnique;
    private $isAutoIncrement;
    private $isLazyLoad;
    private $referrers;
    private $isPrimaryString;

    // only one type is supported currently, which assumes the
    // column either contains the classnames or a key to
    // classnames specified in the schema.    Others may be
    // supported later.

    private $inheritanceType;
    private $isInheritance;
    private $isEnumeratedClasses;
    private $inheritanceList;

    // maybe this can be retrieved from vendor specific information
    private $needsTransactionInPostgres;

    protected $valueSet;

    /**
     * Creates a new column and set the name.
     *
     * @param string $name The column's name
     * @param string $type The column's type
     * @param string $size The column's size
     */
    public function __construct($name = null, $type = null, $size = null)
    {
        parent::__construct();

        if (null !== $name) {
            $this->setName($name);
        }

        if (null !== $type) {
            $this->setType($type);
        }

        if (null !== $size) {
            $this->setSize($size);
        }

        $this->isAutoIncrement            = false;
        $this->isEnumeratedClasses        = false;
        $this->isLazyLoad                 = false;
        $this->isNestedSetLeftKey         = false;
        $this->isNestedSetRightKey        = false;
        $this->isNodeKey                  = false;
        $this->isNotNull                  = false;
        $this->isPrimaryKey               = false;
        $this->isPrimaryString            = false;
        $this->isTreeScopeKey             = false;
        $this->isUnique                   = false;
        $this->needsTransactionInPostgres = false;
        $this->valueSet = [];
    }

    /**
     * @return mixed
     */
    public function getTypeHint()
    {
        return $this->typeHint;
    }

    /**
     * @param mixed $typeHint
     */
    public function setTypeHint($typeHint)
    {
        $this->typeHint = $typeHint;
    }

    protected function setupObject()
    {
        try {
            $database = $this->getDatabase();
            $domain   = $this->getDomain();

            $platform = null;
            if ($this->hasPlatform()) {
                $platform = $this->getPlatform();
            }

            $dom = $this->getAttribute('domain');
            if ($dom) {
                $domain->copy($database->getDomain($dom));
            } else {
                $type = strtoupper($this->getAttribute('type'));
                if ($type) {
                    if ($platform) {
                        $domain->copy($platform->getDomainForType($type));
                    } else {
                        // no platform - probably during tests
                        $this->setDomain(new Domain($type));
                    }
                } else {
                    if ($platform) {
                        $domain->copy($platform->getDomainForType(self::DEFAULT_TYPE));
                    } else {
                        // no platform - probably during tests
                        $this->setDomain(new Domain(self::DEFAULT_TYPE));
                    }
                }
            }

            $this->name = $this->getAttribute('name');
            $this->phpName = $this->getAttribute('phpName');
            $this->phpSingularName = $this->getAttribute('phpSingularName');
            $this->phpType = $this->getAttribute('phpType');
            $this->typeHint = $this->getAttribute('typeHint');
            $this->tableMapName = $this->getAttribute('tableMapName');
            $this->description = $this->getAttribute('description');

            /*
                Retrieves the method for converting from specified name
                to a PHP name, defaulting to parent tables default method.
            */
            $this->phpNamingMethod = $this->getAttribute('phpNamingMethod', $database->getDefaultPhpNamingMethod());

            $this->namePrefix = $this->getAttribute(
                'prefix',
                $this->parentTable->getAttribute('columnPrefix')
            );

            // Accessor visibility
            $visibility = $this->getMethodVisibility('accessorVisibility', 'defaultAccessorVisibility');
            $this->setAccessorVisibility($visibility);

            // Mutator visibility
            $visibility = $this->getMethodVisibility('mutatorVisibility', 'defaultMutatorVisibility');
            $this->setMutatorVisibility($visibility);

            $this->isPrimaryString = $this->booleanValue($this->getAttribute('primaryString'));

            $this->isPrimaryKey = $this->booleanValue($this->getAttribute('primaryKey'));

            $this->isNodeKey = $this->booleanValue($this->getAttribute('nodeKey'));
            $this->nodeKeySep = $this->getAttribute('nodeKeySep', '.');

            $this->isNestedSetLeftKey = $this->booleanValue($this->getAttribute('nestedSetLeftKey'));
            $this->isNestedSetRightKey = $this->booleanValue($this->getAttribute('nestedSetRightKey'));
            $this->isTreeScopeKey = $this->booleanValue($this->getAttribute('treeScopeKey'));

            $this->isNotNull = ($this->booleanValue($this->getAttribute('required'), false) || $this->isPrimaryKey); // primary keys are required

            // AutoIncrement/Sequences
            $this->isAutoIncrement = $this->booleanValue($this->getAttribute('autoIncrement'));
            $this->isLazyLoad = $this->booleanValue($this->getAttribute('lazyLoad'));

            // Add type, size information to associated Domain object
            $domain->replaceSqlType($this->getAttribute('sqlType'));
            if (!$this->getAttribute('size')
                && $domain->getType() === 'VARCHAR'
                && !$this->getAttribute('sqlType')
                && $platform
                && !$platform->supportsVarcharWithoutSize()) {
                $size = 255;
            } else {
                $size = $this->getAttribute('size');
            }

            $domain->replaceSize($size);
            $domain->replaceScale($this->getAttribute('scale'));

            $defval = $this->getAttribute('defaultValue', $this->getAttribute('default'));
            if (null !== $defval && 'null' !== strtolower($defval)) {
                $domain->setDefaultValue(new ColumnDefaultValue($defval, ColumnDefaultValue::TYPE_VALUE));
            } elseif (null !== $this->getAttribute('defaultExpr')) {
                $domain->setDefaultValue(new ColumnDefaultValue($this->getAttribute('defaultExpr'), ColumnDefaultValue::TYPE_EXPR));
            }

            if ($this->getAttribute('valueSet')) {
                $this->setValueSet($this->getAttribute('valueSet'));
            }

            $this->inheritanceType = $this->getAttribute('inheritance');

            /*
                here we are only checking for 'false', so don't
                use booleanValue()
            */
            $this->isInheritance = (null !== $this->inheritanceType && 'false' !== $this->inheritanceType);
        } catch (\Exception $e) {
            throw new EngineException(sprintf(
                'Error setting up column %s: %s',
                $this->getAttribute('name'),
                $e->getMessage()
            ));
        }
    }

    /**
     * Returns the generated methods visibility by looking for the
     * attribute value in the column, parent table or parent database.
     * Finally, it defaults to the default visibility (public).
     *
     * @param  string $attribute       Local column attribute
     * @param  string $parentAttribute Parent (table or database) attribute
     * @return string
     */
    private function getMethodVisibility($attribute, $parentAttribute)
    {
        $database = $this->getDatabase();

        $visibility = $this->getAttribute(
            $attribute,
            $this->parentTable->getAttribute(
                $parentAttribute,
                $database->getAttribute(
                    $parentAttribute,
                    self::DEFAULT_VISIBILITY
                )
            )
        );

        return $visibility;
    }

    /**
     * Returns the database object the current column is in.
     *
     * @return Database
     */
    private function getDatabase()
    {
        return $this->parentTable->getDatabase();
    }

    /**
     * Gets domain for this column, creating a new empty domain object if none is set.
     * @return Domain
     */
    public function getDomain()
    {
        if (null === $this->domain) {
            $this->domain = new Domain();
        }

        return $this->domain;
    }

    /**
     * Sets the domain for this column.
     *
     * @param Domain $domain
     */
    public function setDomain(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Returns the fully qualified column name (table.column).
     *
     * @return string
     */
    public function getFullyQualifiedName()
    {
        return $this->parentTable->getName() . '.' . strtoupper($this->getName());
    }

    /**
     * Returns the column name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the lowercased column name.
     *
     * @return string
     */
    public function getLowercasedName()
    {
        return strtolower($this->name);
    }

    /**
     * Returns the uppercased column name.
     *
     * @return string
     */
    public function getUppercasedName()
    {
        return strtoupper($this->name);
    }

    /**
     * Sets the column name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns whether or not the column name is plural.
     *
     * @return boolean
     */
    public function isNamePlural()
    {
        return $this->getSingularName() !== $this->name;
    }

    /**
     * Returns the column singular name.
     *
     * @return string
     */
    public function getSingularName()
    {
        if ($this->getAttribute('phpSingularName')) return $this->getAttribute('phpSingularName');
        return rtrim($this->name, 's');
    }

    /**
     * Returns the column description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the column description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the name to use in PHP sources. It will set & return
     * a self-generated phpName from its name if its not already set.
     *
     * @return string
     */
    public function getPhpName()
    {
        if (null === $this->phpName) {
            $this->setPhpName();
        }

        return $this->phpName;
    }

    /**
     * Returns the singular form of the name to use in PHP sources. 
     * It will set & return a self-generated phpName from its name 
     * if its not already set.
     *
     * @return string
     */
    public function getPhpSingularName()
    {
        if (null === $this->phpSingularName) {
            $this->setPhpSingularName();
        }

        return $this->phpSingularName;
    }

    /**
     * Sets the name to use in PHP sources.
     *
     * It will generate a phpName from its name if no
     * $phpName is passed.
     *
     * @param string $phpName
     */
    public function setPhpName($phpName = null)
    {
        if (null === $phpName) {
            $this->phpName = self::generatePhpName($this->name, $this->phpNamingMethod, $this->namePrefix);
        } else {
            $this->phpName = $phpName;
        }
    }

    /**
     * Sets the singular forn of the name to use in PHP 
     * sources.
     *
     * It will generate a phpName from its name if no
     * $phpSingularName is passed.
     *
     * @param string $phpSingularName
     */
    public function setPhpSingularName($phpSingularName = null)
    {
        if (null === $phpSingularName) {
            $this->phpSingularName = self::generatePhpSingularName($this->getPhpName());
        } else {
            $this->phpSingularName = $phpSingularName;
        }
    }

    /**
     * Returns the camelCase version of the PHP name.
     *
     * The studly name is the PHP name with the first character lowercase.
     *
     * @return string
     */
    public function getCamelCaseName()
    {
        return lcfirst($this->getPhpName());
    }

    /**
     * Returns the accessor methods visibility of this column / attribute.
     *
     * @return string
     */
    public function getAccessorVisibility()
    {
        if (null !== $this->accessorVisibility) {
            return $this->accessorVisibility;
        }

        return self::DEFAULT_VISIBILITY;
    }

    /**
     * Sets the accessor methods visibility for this column / attribute.
     *
     * @param string $visibility
     */
    public function setAccessorVisibility($visibility)
    {
        $visibility = strtolower($visibility);
        if (!in_array($visibility, self::$validVisibilities)) {
            $visibility = self::DEFAULT_VISIBILITY;
        }

        $this->accessorVisibility = $visibility;
    }

    /**
     * Returns the mutator methods visibility for this current column.
     *
     * @return string
     */
    public function getMutatorVisibility()
    {
        if (null !== $this->mutatorVisibility) {
            return $this->mutatorVisibility;
        }

        return self::DEFAULT_VISIBILITY;
    }

    /**
     * Sets the mutator methods visibility for this column / attribute.
     *
     * @param string $visibility
     */
    public function setMutatorVisibility($visibility)
    {
        $visibility = strtolower($visibility);
        if (!in_array($visibility, self::$validVisibilities)) {
            $visibility = self::DEFAULT_VISIBILITY;
        }

        $this->mutatorVisibility = $visibility;
    }

    /**
     * Returns the full column constant name (e.g. TableMapName::COL_COLUMN_NAME).
     *
     * @return string A column constant name for insertion into PHP code
     */
    public function getFQConstantName()
    {
        $classname = $this->parentTable->getPhpName() . 'TableMap';
        $const = $this->getConstantName();

        return $classname.'::'.$const;
    }

    /**
     * Returns the column constant name.
     *
     * @return string
     */
    public function getConstantName()
    {
        // was it overridden in schema.xml ?
        if ($this->getTableMapName()) {
            return self::CONSTANT_PREFIX.strtoupper($this->getTableMapName());
        }

        return self::CONSTANT_PREFIX.strtoupper($this->getName());
    }

    /**
     * Returns the TableMap constant name that will identify this column.
     *
     * @return string
     */
    public function getTableMapName()
    {
        return $this->tableMapName;
    }

    /**
     * Sets the TableMap constant name that will identify this column.
     *
     * @param string $name
     */
    public function setTableMapName($name)
    {
        $this->tableMapName = $name;
    }

    /**
     * Returns the type to use in PHP sources.
     *
     * If no types has been specified, then use result of getPhpNative().
     *
     * @return string
     */
    public function getPhpType()
    {
        return $this->phpType ? $this->phpType : $this->getPhpNative();
    }

    /**
     * Returns the location of this column within the table (one-based).
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Returns the location of this column within the table (one-based).
     *
     * @param integer $position
     */
    public function setPosition($position)
    {
        $this->position = (int) $position;
    }

    /**
     * Sets the parent table.
     *
     * @param Table $table
     */
    public function setTable(Table $table)
    {
        $this->parentTable = $table;
    }

    /**
     * Returns the parent table.
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->parentTable;
    }

    /**
     * Returns the parent table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->parentTable->getName();
    }

    /**
     * Adds a new inheritance definition to the inheritance list and sets the
     * parent column of the inheritance to the current column.
     *
     * @param  Inheritance|array $inheritance
     * @return Inheritance
     */
    public function addInheritance($inheritance)
    {
        if ($inheritance instanceof Inheritance) {
            $inheritance->setColumn($this);
            if (null === $this->inheritanceList) {
                $this->inheritanceList = [];
                $this->isEnumeratedClasses = true;
            }
            $this->inheritanceList[] = $inheritance;

            return $inheritance;
        }

        $inh = new Inheritance();
        $inh->loadMapping($inheritance);

        return $this->addInheritance($inh);
    }

    /**
     * Returns the inheritance type.
     *
     * @return string
     */
    public function getInheritanceType()
    {
        return $this->inheritanceType;
    }

    /**
     * Returns the inheritance list.
     *
     * @return Inheritance[]
     */
    public function getInheritanceList()
    {
        return $this->inheritanceList;
    }

    /**
     * Returns the inheritance definitions.
     *
     * @return Inheritance[]
     */
    public function getChildren()
    {
        return $this->inheritanceList;
    }

    /**
     * Returns whether or not this column is a normal property or specifies a
     * the classes that are represented in the table containing this column.
     *
     * @return boolean
     */
    public function isInheritance()
    {
        return $this->isInheritance;
    }

    /**
     * Returns whether or not possible classes have been enumerated in the
     * schema file.
     *
     * @return boolean
     */
    public function isEnumeratedClasses()
    {
        return $this->isEnumeratedClasses;
    }

    /**
     * Returns whether or not the column is not null.
     *
     * @return boolean
     */
    public function isNotNull()
    {
        return $this->isNotNull;
    }

    /**
     * Sets whether or not the column is not null.
     *
     * @param boolean $flag
     */
    public function setNotNull($flag = true)
    {
        $this->isNotNull = (Boolean) $flag;
    }

    /**
     * Returns NOT NULL string for this column.
     *
     * @return string.
     */
    public function getNotNullString()
    {
        return $this->parentTable->getPlatform()->getNullString($this->isNotNull);
    }

    /**
     * Sets whether or not the column is used as the primary string.
     *
     * The primary string is the value used by default in the magic
     * __toString method of an active record object.
     *
     * @param boolean $isPrimaryString
     */
    public function setPrimaryString($isPrimaryString)
    {
        $this->isPrimaryString = (Boolean) $isPrimaryString;
    }

    /**
     * Returns true if the column is the primary string (used for the magic
     * __toString() method).
     *
     * @return boolean
     */
    public function isPrimaryString()
    {
        return $this->isPrimaryString;
    }

    /**
     * Sets whether or not the column is a primary key.
     *
     * @param boolean $flag
     */
    public function setPrimaryKey($flag = true)
    {
        $this->isPrimaryKey = (Boolean) $flag;
    }

    /**
     * Returns whether or not the column is the primary key.
     *
     * @return boolean
     */
    public function isPrimaryKey()
    {
        return $this->isPrimaryKey;
    }

    /**
     * Sets whether or not the column is a node key of a tree.
     *
     * @param boolean $isNodeKey
     */
    public function setNodeKey($isNodeKey)
    {
        $this->isNodeKey = (Boolean) $isNodeKey;
    }

    /**
     * Returns whether or not the column is a node key of a tree.
     *
     * @return boolean
     */
    public function isNodeKey()
    {
        return $this->isNodeKey;
    }

    /**
     * Sets the separator for the node key column in a tree.
     *
     * @param string $sep
     */
    public function setNodeKeySep($sep)
    {
        $this->nodeKeySep = (string) $sep;
    }

    /**
     * Returns the node key column separator for a tree.
     *
     * @return string
     */
    public function getNodeKeySep()
    {
        return $this->nodeKeySep;
    }

    /**
     * Sets whether or not the column is the nested set left key of a tree.
     *
     * @param boolean $isNestedSetLeftKey
     */
    public function setNestedSetLeftKey($isNestedSetLeftKey)
    {
        $this->isNestedSetLeftKey = (Boolean) $isNestedSetLeftKey;
    }

    /**
     * Returns whether or not the column is a nested set key of a tree.
     *
     * @return boolean
     */
    public function isNestedSetLeftKey()
    {
        return $this->isNestedSetLeftKey;
    }

    /**
     * Set if the column is the nested set right key of a tree.
     *
     * @param boolean $isNestedSetRightKey
     */
    public function setNestedSetRightKey($isNestedSetRightKey)
    {
        $this->isNestedSetRightKey = (Boolean) $isNestedSetRightKey;
    }

    /**
     * Return whether or not the column is a nested set right key of a tree.
     *
     * @return boolean
     */
    public function isNestedSetRightKey()
    {
        return $this->isNestedSetRightKey;
    }

    /**
     * Sets whether or not the column is the scope key of a tree.
     *
     * @param boolean $isTreeScopeKey
     */
    public function setTreeScopeKey($isTreeScopeKey)
    {
        $this->isTreeScopeKey = (Boolean) $isTreeScopeKey;
    }

    /**
     * Returns whether or not the column is a scope key of a tree.
     *
     * @return boolean
     */
    public function isTreeScopeKey()
    {
        return $this->isTreeScopeKey;
    }

    /**
     * Returns whether or not the column must have a unique index.
     *
     * @return boolean
     */
    public function isUnique()
    {
        return $this->isUnique;
    }

    /**
     * Returns true if the column requires a transaction in PostGreSQL.
     *
     * @return boolean
     */
    public function requiresTransactionInPostgres()
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * Returns whether or not this column is a foreign key.
     *
     * @return boolean
     */
    public function isForeignKey()
    {
        return count($this->getForeignKeys()) > 0;
    }

    /**
     * Returns whether or not this column is part of more than one foreign key.
     *
     * @return boolean
     */
    public function hasMultipleFK()
    {
        return count($this->getForeignKeys()) > 1;
    }

    /**
     * Returns the foreign key objects for this column.
     *
     * Only if it is a foreign key or part of a foreign key.
     *
     * @return ForeignKey[]
     */
    public function getForeignKeys()
    {
        return $this->parentTable->getColumnForeignKeys($this->name);
    }

    /**
     * Adds the foreign key from another table that refers to this column.
     *
     * @param ForeignKey $fk
     */
    public function addReferrer(ForeignKey $fk)
    {
        if (null === $this->referrers) {
            $this->referrers = [];
        }

        $this->referrers[] = $fk;
    }

    /**
     * Returns the list of references to this column.
     *
     * @return ForeignKey[]
     */
    public function getReferrers()
    {
        if (null === $this->referrers) {
            $this->referrers = [];
        }

        return $this->referrers;
    }

    /**
     * Returns whether or not this column has referers.
     *
     * @return boolean
     */
    public function hasReferrers()
    {
        return is_array($this->referrers) && count($this->referrers) > 0;
    }

    /**
     * Returns whether or not this column has a specific referrer for a
     * specific foreign key object.
     *
     * @param  ForeignKey $fk
     * @return boolean
     */
    public function hasReferrer(ForeignKey $fk)
    {
        return $this->hasReferrers() && in_array($fk, $this->referrers, true);
    }

    /**
     * Clears all referrers.
     *
     */
    public function clearReferrers()
    {
        $this->referrers = null;
    }

    /**
     * Clears all inheritance children.
     *
     */
    public function clearInheritanceList()
    {
        $this->inheritanceList = [];
    }

    /**
     * Sets the domain up for specified mapping type.
     *
     * Calling this method will implicitly overwrite any previously set type,
     * size, scale (or other domain attributes).
     *
     * @param string $mappingType
     */
    public function setDomainForType($mappingType)
    {
        $this->getDomain()->copy($this->getPlatform()->getDomainForType($mappingType));
    }

    /**
     * Sets the mapping column type.
     *
     * @param string $mappingType
     * @see Domain::setType()
     */
    public function setType($mappingType)
    {
        $this->getDomain()->setType($mappingType);

        if (in_array($mappingType, [ PropelTypes::VARBINARY, PropelTypes::LONGVARBINARY, PropelTypes::BLOB ])) {
            $this->needsTransactionInPostgres = true;
        }
    }

    /**
     * Returns the Propel column type as a string.
     *
     * @return string
     * @see Domain::getType()
     */
    public function getType()
    {
        return $this->getDomain()->getType();
    }

    /**
     * Returns the column PDO type integer for this column's mapping type.
     *
     * @return integer
     */
    public function getPDOType()
    {
        return PropelTypes::getPDOType($this->getType());
    }

    public function isDefaultSqlType(PlatformInterface $platform = null)
    {
        if (null === $this->domain
            || null === $this->domain->getSqlType()
            || null === $platform) {
            return true;
        }

        $defaultSqlType = $platform->getDomainForType($this->getType())->getSqlType();

        return $defaultSqlType === $this->getDomain()->getSqlType();
    }

    /**
     * Returns whether or not this column is a blob/lob type.
     *
     * @return boolean
     */
    public function isLobType()
    {
        return PropelTypes::isLobType($this->getType());
    }

    /**
     * Returns whether or not this column is a text type.
     *
     * @return boolean
     */
    public function isTextType()
    {
        return PropelTypes::isTextType($this->getType());
    }

    /**
     * Returns whether or not this column is a numeric type.
     *
     * @return boolean
     */
    public function isNumericType()
    {
        return PropelTypes::isNumericType($this->getType());
    }

    /**
     * Returns whether or not this column is a boolean type.
     *
     * @return boolean
     */
    public function isBooleanType()
    {
        return PropelTypes::isBooleanType($this->getType());
    }

    /**
     * Returns whether or not this column is a temporal type.
     *
     * @return boolean
     */
    public function isTemporalType()
    {
        return PropelTypes::isTemporalType($this->getType());
    }

    /**
     * Returns whether or not the column is an array column.
     *
     * @return boolean
     */
    public function isPhpArrayType()
    {
        return PropelTypes::isPhpArrayType($this->getType());
    }

    /**
     * Returns whether or not this column is an ENUM column.
     *
     * @return boolean
     */
    public function isEnumType()
    {
        return $this->getType() === PropelTypes::ENUM;
    }

    /**
     * Sets the list of possible values for an ENUM column.
     *
     * @param array|string
     */
    public function setValueSet($valueSet)
    {
        if (is_string($valueSet)) {
            $valueSet = explode(',', $valueSet);
            $valueSet = array_map('trim', $valueSet);
        }

        $this->valueSet = $valueSet;
    }

    /**
     * Returns the list of possible values for an ENUM column.
     *
     * @return array
     */
    public function getValueSet()
    {
        return $this->valueSet;
    }

    /**
     * Returns the column size.
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->domain ? $this->domain->getSize() : false;
    }

    /**
     * Sets the column size.
     *
     * @param integer $size
     */
    public function setSize($size)
    {
        $this->domain->setSize($size);
    }

    /**
     * Returns the column scale.
     *
     * @return integer
     */
    public function getScale()
    {
        return $this->domain->getScale();
    }

    /**
     * Sets the column scale.
     *
     * @param integer $scale
     */
    public function setScale($scale)
    {
        $this->domain->setScale($scale);
    }

    /**
     * Returns the size and precision in brackets for use in an SQL DLL.
     *
     * Example: (size[,scale]) <-> (10) or (10,2)
     *
     * return string
     */
    public function getSizeDefinition()
    {
        return $this->domain->getSizeDefinition();
    }

    /**
     * Returns true if this table has a default value (and which is not NULL).
     *
     * @return bool
     */
    public function hasDefaultValue()
    {
        return null !== $this->getDefaultValue();
    }

    /**
     * Returns a string that will give this column a default value in PHP.
     *
     * @return string
     */
    public function getDefaultValueString()
    {
        $defaultValue = $this->getDefaultValue();

        if (null === $defaultValue) {
            return 'null';
        }

        if ($this->isNumericType()) {
            return (float) $defaultValue->getValue();
        }

        if ($this->isTextType() || $this->getDefaultValue()->isExpression()) {
            return sprintf("'%s'", str_replace("'", "\'", $defaultValue->getValue()));
        }

        if ($this->getType() === PropelTypes::BOOLEAN) {
            return $this->booleanValue($defaultValue->getValue()) ? 'true' : 'false';
        }

        return sprintf("'%s'", $defaultValue->getValue());
    }

    /**
     * Sets a string that will give this column a default value.
     *
     * @param  ColumnDefaultValue|mixed $defaultValue The column's default value
     * @return Column
     */
    public function setDefaultValue($defaultValue)
    {
        if (!$defaultValue instanceof ColumnDefaultValue) {
            $defaultValue = new ColumnDefaultValue($defaultValue, ColumnDefaultValue::TYPE_VALUE);
        }

        $this->domain->setDefaultValue($defaultValue);
    }

    /**
     * Returns the default value object for this column.
     *
     * @return ColumnDefaultValue
     * @see Domain::getDefaultValue()
     */
    public function getDefaultValue()
    {
        return $this->domain->getDefaultValue();
    }

    /**
     * Returns the default value suitable for use in PHP.
     *
     * @return mixed
     * @see Domain::getPhpDefaultValue()
     */
    public function getPhpDefaultValue()
    {
        return $this->domain->getPhpDefaultValue();
    }

    /**
     * Returns whether or the column is an auto increment/sequence value for
     * the target database. We need to pass in the properties for the target
     * database!
     *
     * @return boolean
     */
    public function isAutoIncrement()
    {
        return $this->isAutoIncrement;
    }

    /**
     * Return whether or not the column has to be lazy loaded.
     *
     * For example, if a runtime query on the table doesn't hydrate this column
     * but a getter does.
     *
     * @return boolean
     */
    public function isLazyLoad()
    {
        return $this->isLazyLoad;
    }

    /**
     * Returns the auto-increment string.
     *
     * @return string
     */
    public function getAutoIncrementString()
    {
        if ($this->isAutoIncrement() && IdMethod::NATIVE === $this->parentTable->getIdMethod()) {
            return $this->getPlatform()->getAutoIncrement();
        }

        if ($this->isAutoIncrement()) {
            throw new EngineException(sprintf(
                'You have specified autoIncrement for column "%s", but you have not specified idMethod="native" for table "%s".',
                $this->name,
                $this->parentTable->getName()
            ));
        }

        return '';
    }

    /**
     * Sets whether or not this column is an auto incremented value.
     *
     * Use isAutoIncrement() to find out if it is set or not.
     *
     * @param boolean $flag
     */
    public function setAutoIncrement($flag = true)
    {
        $this->isAutoIncrement = (Boolean) $flag;
    }

    /**
     * Returns a string representation of the native PHP type which corresponds
     * to the Propel type of this column. Used in the generation of Base
     * objects.
     *
     * @return string
     */
    public function getPhpNative()
    {
        return PropelTypes::getPhpNative($this->getType());
    }

    /**
     * Returns whether or not the column PHP native type is primitive type (aka
     * a boolean, an integer, a long, a float, a double or a string).
     *
     * @return boolean
     * @see PropelTypes::isPhpPrimitiveType()
     */
    public function isPhpPrimitiveType()
    {
        return PropelTypes::isPhpPrimitiveType($this->getPhpType());
    }

    /**
     * Returns whether or not the column PHP native type is a primitive numeric
     * type (aka an integer, a long, a float or a double).
     *
     * @return boolean
     * @see PropelTypes::isPhpPrimitiveNumericType()
     */
    public function isPhpPrimitiveNumericType()
    {
        return PropelTypes::isPhpPrimitiveNumericType($this->getPhpType());
    }

    /**
     * Returns whether or not the column PHP native type is an object.
     *
     * @return boolean
     * @see PropelTypes::isPhpObjectType()
     */
    public function isPhpObjectType()
    {
        return PropelTypes::isPhpObjectType($this->getPhpType());
    }

    /**
     * Returns an instance of PlatformInterface interface.
     *
     * @return PlatformInterface
     */
    public function getPlatform()
    {
        return $this->parentTable->getPlatform();
    }

    /**
     * Returns whether or not this column has a platform adapter.
     *
     * @return boolean
     */
    public function hasPlatform()
    {
        if (null === $this->parentTable) {
            return false;
        }

        return $this->parentTable->getPlatform() ? true : false;
    }

    /**
     * Clones the current object.
     *
     */
    public function __clone()
    {
        $this->referrers = null;
        if ($this->domain) {
            $this->domain = clone $this->domain;
        }
    }

    /**
     * Returns a generated PHP name.
     *
     * @param  string $name
     * @param  string $phpNamingMethod
     * @param  string $namePrefix
     * @return string
     */
    public static function generatePhpName($name, $phpNamingMethod = PhpNameGenerator::CONV_METHOD_CLEAN, $namePrefix = null)
    {
        return NameFactory::generateName(NameFactory::PHP_GENERATOR, [ $name, $phpNamingMethod, $namePrefix ]);
    }

    /**
     * Generates the singular form of a PHP name.
     *
     * @param  string $phpname
     * @return string
     */
    public static function generatePhpSingularName($phpname)
    {
        return rtrim($phpname, 's');
    }
}
