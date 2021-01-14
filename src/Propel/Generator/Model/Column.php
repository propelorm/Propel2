<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use Exception;
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
    public const DEFAULT_TYPE = 'VARCHAR';
    public const DEFAULT_VISIBILITY = 'public';
    public const CONSTANT_PREFIX = 'COL_';

    /**
     * @var string[]
     */
    public static $validVisibilities = [
        'public',
        'protected',
        'private',
    ];

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $phpName;

    /**
     * @var string|null
     */
    private $phpSingularName;

    /**
     * @var string|null
     */
    private $phpNamingMethod;

    /**
     * @var bool
     */
    private $isNotNull = false;

    /**
     * @var string|null
     */
    private $namePrefix;

    /**
     * @var string|null
     */
    private $accessorVisibility;

    /**
     * @var string|null
     */
    private $mutatorVisibility;

    /**
     * @var string|null
     */
    private $typeHint;

    /**
     * The name to use for the tableMap constant that identifies this column.
     * (Will be converted to all-uppercase in the templates.)
     *
     * @var string
     */
    private $tableMapName;

    /**
     * Native PHP type (scalar or class name)
     *
     * @var string "string", "boolean", "int", "double"
     */
    private $phpType;

    /**
     * @var \Propel\Generator\Model\Domain|null
     */
    private $domain;

    /**
     * @var \Propel\Generator\Model\Table
     */
    private $parentTable;

    /**
     * @var int|null
     */
    private $position;

    /**
     * @var bool
     */
    private $isPrimaryKey = false;

    /**
     * @var bool
     */
    private $isNodeKey = false;

    /**
     * @var string
     */
    private $nodeKeySep;

    /**
     * @var bool
     */
    private $isNestedSetLeftKey = false;

    /**
     * @var bool
     */
    private $isNestedSetRightKey = false;

    /**
     * @var bool
     */
    private $isTreeScopeKey = false;

    /**
     * @var bool
     */
    private $isUnique = false;

    /**
     * @var bool
     */
    private $isAutoIncrement = false;

    /**
     * @var bool
     */
    private $isLazyLoad = false;

    /**
     * @var array
     */
    private $referrers = [];

    /**
     * @var bool
     */
    private $isPrimaryString = false;

    // only one type is supported currently, which assumes the
    // column either contains the classnames or a key to
    // classnames specified in the schema.    Others may be
    // supported later.

    /**
     * @var string|null
     */
    private $inheritanceType;

    /**
     * @var bool
     */
    private $isInheritance = false;

    /**
     * @var bool
     */
    private $isEnumeratedClasses = false;

    /**
     * @var array|null
     */
    private $inheritanceList;

    /**
     * maybe this can be retrieved from vendor specific information
     *
     * @var bool
     */
    private $needsTransactionInPostgres = false;

    /**
     * @var string[]
     */
    protected $valueSet = [];

    /**
     * Creates a new column and set the name.
     *
     * @param string|null $name The column's name
     * @param string|null $type The column's type
     * @param string|int|null $size The column's size
     */
    public function __construct($name = null, $type = null, $size = null)
    {
        if ($name !== null) {
            $this->setName($name);
        }

        if ($type !== null) {
            $this->setType($type);
        }

        if ($size !== null) {
            $this->setSize((int)$size);
        }
    }

    /**
     * @return string|null
     */
    public function getTypeHint()
    {
        return $this->typeHint;
    }

    /**
     * @param string|null $typeHint
     *
     * @return void
     */
    public function setTypeHint($typeHint)
    {
        $this->typeHint = $typeHint;
    }

    /**
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return void
     */
    protected function setupObject()
    {
        try {
            $database = $this->getDatabase();
            $domain = $this->getDomain();

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

            $this->isNotNull = ($this->booleanValue($this->getAttribute('required')) || $this->isPrimaryKey); // primary keys are required

            // AutoIncrement/Sequences
            $this->isAutoIncrement = $this->booleanValue($this->getAttribute('autoIncrement'));
            $this->isLazyLoad = $this->booleanValue($this->getAttribute('lazyLoad'));

            // Add type, size information to associated Domain object
            $domain->replaceSqlType($this->getAttribute('sqlType'));

            if (
                !$this->getAttribute('size')
                && $domain->getType() === 'VARCHAR'
                && !$this->getAttribute('sqlType')
                && $platform
                && !$platform->supportsVarcharWithoutSize()
            ) {
                $size = 255;
            } else {
                $size = $this->getAttribute('size') ? (int)$this->getAttribute('size') : null;
            }
            $domain->replaceSize($size);

            $scale = $this->getAttribute('scale') ? (int)$this->getAttribute('scale') : null;
            $domain->replaceScale($scale);

            $defval = $this->getAttribute('defaultValue', $this->getAttribute('default'));
            if ($defval !== null && strtolower($defval) !== 'null') {
                $domain->setDefaultValue(new ColumnDefaultValue($defval, ColumnDefaultValue::TYPE_VALUE));
            } elseif ($this->getAttribute('defaultExpr') !== null) {
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
            $this->isInheritance = ($this->inheritanceType !== null && $this->inheritanceType !== 'false');
        } catch (Exception $e) {
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
     * @param string $attribute Local column attribute
     * @param string $parentAttribute Parent (table or database) attribute
     *
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
     * @return \Propel\Generator\Model\Database
     */
    private function getDatabase()
    {
        return $this->parentTable->getDatabase();
    }

    /**
     * Gets domain for this column, creating a new empty domain object if none is set.
     *
     * @return \Propel\Generator\Model\Domain
     */
    public function getDomain()
    {
        $domain = $this->domain;
        if ($domain === null) {
            $domain = new Domain();
            $this->domain = $domain;
        }

        return $domain;
    }

    /**
     * Sets the domain for this column.
     *
     * @param \Propel\Generator\Model\Domain $domain
     *
     * @return void
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
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns whether or not the column name is plural.
     *
     * @return bool
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
        if ($this->getAttribute('phpSingularName')) {
            return $this->getAttribute('phpSingularName');
        }

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
     *
     * @return void
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
        if ($this->phpName === null) {
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
        if ($this->phpSingularName === null) {
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
     * @param string|null $phpName
     *
     * @return void
     */
    public function setPhpName($phpName = null)
    {
        if ($phpName === null) {
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
     * @param string|null $phpSingularName
     *
     * @return void
     */
    public function setPhpSingularName($phpSingularName = null)
    {
        if ($phpSingularName === null) {
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
        if ($this->accessorVisibility !== null) {
            return $this->accessorVisibility;
        }

        return self::DEFAULT_VISIBILITY;
    }

    /**
     * Sets the accessor methods visibility for this column / attribute.
     *
     * @param string $visibility
     *
     * @return void
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
        if ($this->mutatorVisibility !== null) {
            return $this->mutatorVisibility;
        }

        return self::DEFAULT_VISIBILITY;
    }

    /**
     * Sets the mutator methods visibility for this column / attribute.
     *
     * @param string $visibility
     *
     * @return void
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

        return $classname . '::' . $const;
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
            return self::CONSTANT_PREFIX . strtoupper($this->getTableMapName());
        }

        return self::CONSTANT_PREFIX . strtoupper($this->getName());
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
     *
     * @return void
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
     * @return int|null
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Returns the location of this column within the table (one-based).
     *
     * @param int $position
     *
     * @return void
     */
    public function setPosition($position)
    {
        $this->position = (int)$position;
    }

    /**
     * Sets the parent table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function setTable(Table $table)
    {
        $this->parentTable = $table;
    }

    /**
     * Returns the parent table.
     *
     * @return \Propel\Generator\Model\Table
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
     * @param \Propel\Generator\Model\Inheritance|array $inheritance
     *
     * @return \Propel\Generator\Model\Inheritance
     */
    public function addInheritance($inheritance)
    {
        if ($inheritance instanceof Inheritance) {
            $inheritance->setColumn($this);
            if ($this->inheritanceList === null) {
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
     * @return string|null
     */
    public function getInheritanceType()
    {
        return $this->inheritanceType;
    }

    /**
     * Returns the inheritance list.
     *
     * @return \Propel\Generator\Model\Inheritance[]
     */
    public function getInheritanceList()
    {
        return $this->inheritanceList;
    }

    /**
     * Returns the inheritance definitions.
     *
     * @return \Propel\Generator\Model\Inheritance[]
     */
    public function getChildren()
    {
        return $this->inheritanceList;
    }

    /**
     * Returns whether or not this column is a normal property or specifies a
     * the classes that are represented in the table containing this column.
     *
     * @return bool
     */
    public function isInheritance()
    {
        return $this->isInheritance;
    }

    /**
     * Returns whether or not possible classes have been enumerated in the
     * schema file.
     *
     * @return bool
     */
    public function isEnumeratedClasses()
    {
        return $this->isEnumeratedClasses;
    }

    /**
     * Returns whether or not the column is not null.
     *
     * @return bool
     */
    public function isNotNull()
    {
        return $this->isNotNull;
    }

    /**
     * Sets whether or not the column is not null.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setNotNull($flag = true)
    {
        $this->isNotNull = (bool)$flag;
    }

    /**
     * Returns NOT NULL string for this column.
     *
     * @return string
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
     * @param bool $isPrimaryString
     *
     * @return void
     */
    public function setPrimaryString($isPrimaryString)
    {
        $this->isPrimaryString = (bool)$isPrimaryString;
    }

    /**
     * Returns true if the column is the primary string (used for the magic
     * __toString() method).
     *
     * @return bool
     */
    public function isPrimaryString()
    {
        return $this->isPrimaryString;
    }

    /**
     * Sets whether or not the column is a primary key.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setPrimaryKey($flag = true)
    {
        $this->isPrimaryKey = (bool)$flag;
    }

    /**
     * Returns whether or not the column is the primary key.
     *
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->isPrimaryKey;
    }

    /**
     * Sets whether or not the column is a node key of a tree.
     *
     * @param bool $isNodeKey
     *
     * @return void
     */
    public function setNodeKey($isNodeKey)
    {
        $this->isNodeKey = (bool)$isNodeKey;
    }

    /**
     * Returns whether or not the column is a node key of a tree.
     *
     * @return bool
     */
    public function isNodeKey()
    {
        return $this->isNodeKey;
    }

    /**
     * Sets the separator for the node key column in a tree.
     *
     * @param string $sep
     *
     * @return void
     */
    public function setNodeKeySep($sep)
    {
        $this->nodeKeySep = (string)$sep;
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
     * @param bool $isNestedSetLeftKey
     *
     * @return void
     */
    public function setNestedSetLeftKey($isNestedSetLeftKey)
    {
        $this->isNestedSetLeftKey = (bool)$isNestedSetLeftKey;
    }

    /**
     * Returns whether or not the column is a nested set key of a tree.
     *
     * @return bool
     */
    public function isNestedSetLeftKey()
    {
        return $this->isNestedSetLeftKey;
    }

    /**
     * Set if the column is the nested set right key of a tree.
     *
     * @param bool $isNestedSetRightKey
     *
     * @return void
     */
    public function setNestedSetRightKey($isNestedSetRightKey)
    {
        $this->isNestedSetRightKey = (bool)$isNestedSetRightKey;
    }

    /**
     * Return whether or not the column is a nested set right key of a tree.
     *
     * @return bool
     */
    public function isNestedSetRightKey()
    {
        return $this->isNestedSetRightKey;
    }

    /**
     * Sets whether or not the column is the scope key of a tree.
     *
     * @param bool $isTreeScopeKey
     *
     * @return void
     */
    public function setTreeScopeKey($isTreeScopeKey)
    {
        $this->isTreeScopeKey = (bool)$isTreeScopeKey;
    }

    /**
     * Returns whether or not the column is a scope key of a tree.
     *
     * @return bool
     */
    public function isTreeScopeKey()
    {
        return $this->isTreeScopeKey;
    }

    /**
     * Returns whether or not the column must have a unique index.
     *
     * @return bool
     */
    public function isUnique()
    {
        return $this->isUnique;
    }

    /**
     * Returns true if the column requires a transaction in PostGreSQL.
     *
     * @return bool
     */
    public function requiresTransactionInPostgres()
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * Returns whether or not this column is a foreign key.
     *
     * @return bool
     */
    public function isForeignKey()
    {
        return count($this->getForeignKeys()) > 0;
    }

    /**
     * Returns whether or not this column is part of more than one foreign key.
     *
     * @return bool
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
     * @return \Propel\Generator\Model\ForeignKey[]
     */
    public function getForeignKeys()
    {
        return $this->parentTable->getColumnForeignKeys($this->name);
    }

    /**
     * Adds the foreign key from another table that refers to this column.
     *
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return void
     */
    public function addReferrer(ForeignKey $fk)
    {
        $this->referrers[] = $fk;
    }

    /**
     * Returns the list of references to this column.
     *
     * @return \Propel\Generator\Model\ForeignKey[]
     */
    public function getReferrers()
    {
        return $this->referrers;
    }

    /**
     * Returns whether or not this column has referers.
     *
     * @return bool
     */
    public function hasReferrers()
    {
        return count($this->referrers) > 0;
    }

    /**
     * Returns whether or not this column has a specific referrer for a
     * specific foreign key object.
     *
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return bool
     */
    public function hasReferrer(ForeignKey $fk)
    {
        return $this->referrers && in_array($fk, $this->referrers, true);
    }

    /**
     * Clears all referrers.
     *
     * @return void
     */
    public function clearReferrers()
    {
        $this->referrers = [];
    }

    /**
     * Clears all inheritance children.
     *
     * @return void
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
     *
     * @return void
     */
    public function setDomainForType($mappingType)
    {
        $this->getDomain()->copy($this->getPlatform()->getDomainForType($mappingType));
    }

    /**
     * Sets the mapping column type.
     *
     * @see Domain::setType()
     *
     * @param string $mappingType
     *
     * @return void
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
     * @see Domain::getType()
     *
     * @return string
     */
    public function getType()
    {
        return $this->getDomain()->getType();
    }

    /**
     * Returns the column PDO type integer for this column's mapping type.
     *
     * @return int
     */
    public function getPDOType()
    {
        return PropelTypes::getPDOType($this->getType());
    }

    /**
     * @param \Propel\Generator\Platform\PlatformInterface|null $platform
     *
     * @return bool
     */
    public function isDefaultSqlType(?PlatformInterface $platform = null)
    {
        if (
            $this->domain === null
            || $this->domain->getSqlType() === null
            || $platform === null
        ) {
            return true;
        }

        $defaultSqlType = $platform->getDomainForType($this->getType())->getSqlType();

        return $defaultSqlType === $this->getDomain()->getSqlType();
    }

    /**
     * Returns whether or not this column is a blob/lob type.
     *
     * @return bool
     */
    public function isLobType()
    {
        return PropelTypes::isLobType($this->getType());
    }

    /**
     * Returns whether or not this column is a text type.
     *
     * @return bool
     */
    public function isTextType()
    {
        return PropelTypes::isTextType($this->getType());
    }

    /**
     * Returns whether or not this column is a numeric type.
     *
     * @return bool
     */
    public function isNumericType()
    {
        return PropelTypes::isNumericType($this->getType());
    }

    /**
     * Returns whether or not this column is a boolean type.
     *
     * @return bool
     */
    public function isBooleanType()
    {
        return PropelTypes::isBooleanType($this->getType());
    }

    /**
     * Returns whether or not this column is a temporal type.
     *
     * @return bool
     */
    public function isTemporalType()
    {
        return PropelTypes::isTemporalType($this->getType());
    }

    /**
     * Returns whether or not the column is an array column.
     *
     * @return bool
     */
    public function isPhpArrayType()
    {
        return PropelTypes::isPhpArrayType($this->getType());
    }

    /**
     * Returns whether or not this column is an ENUM or SET column.
     *
     * @return bool
     */
    public function isValueSetType()
    {
        return ($this->isEnumType() || $this->isSetType());
    }

    /**
     * Returns whether or not this column is an ENUM column.
     *
     * @return bool
     */
    public function isEnumType()
    {
        return $this->getType() === PropelTypes::ENUM;
    }

    /**
     * Returns whether or not this column is a SET column.
     *
     * @return bool
     */
    public function isSetType()
    {
        return $this->getType() === PropelTypes::SET;
    }

    /**
     * Sets the list of possible values for an ENUM or SET column.
     *
     * @param string|string[] $valueSet
     *
     * @return void
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
     * Returns the list of possible values for an ENUM or SET column.
     *
     * @return string[]
     */
    public function getValueSet()
    {
        return $this->valueSet;
    }

    /**
     * Returns the column size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->domain ? $this->domain->getSize() : false;
    }

    /**
     * Sets the column size.
     *
     * @param int|null $size
     *
     * @return void
     */
    public function setSize($size)
    {
        $this->domain->setSize($size);
    }

    /**
     * Returns the column scale.
     *
     * @return int
     */
    public function getScale()
    {
        return $this->domain->getScale();
    }

    /**
     * Sets the column scale.
     *
     * @param int $scale
     *
     * @return void
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
     * @return string
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
        return $this->getDefaultValue() !== null;
    }

    /**
     * Returns a string that will give this column a default value in PHP.
     *
     * @return string
     */
    public function getDefaultValueString()
    {
        $defaultValue = $this->getDefaultValue();

        if ($defaultValue === null) {
            return 'null';
        }

        if ($this->isNumericType()) {
            return (string)$defaultValue->getValue();
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
     * @param \Propel\Generator\Model\ColumnDefaultValue|string|null $defaultValue The column's default value
     *
     * @return void
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
     * @see Domain::getDefaultValue()
     *
     * @return \Propel\Generator\Model\ColumnDefaultValue|null
     */
    public function getDefaultValue()
    {
        return $this->domain->getDefaultValue();
    }

    /**
     * Returns the default value suitable for use in PHP.
     *
     * @see Domain::getPhpDefaultValue()
     *
     * @return mixed|null
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
     * @return bool
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
     * @return bool
     */
    public function isLazyLoad()
    {
        return $this->isLazyLoad;
    }

    /**
     * Returns the auto-increment string.
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return string
     */
    public function getAutoIncrementString()
    {
        if ($this->isAutoIncrement() && $this->parentTable->getIdMethod() === IdMethod::NATIVE) {
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
     * @param bool $flag
     *
     * @return void
     */
    public function setAutoIncrement($flag = true)
    {
        $this->isAutoIncrement = (bool)$flag;
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
     * @see PropelTypes::isPhpPrimitiveType()
     *
     * @return bool
     */
    public function isPhpPrimitiveType()
    {
        return PropelTypes::isPhpPrimitiveType($this->getPhpType());
    }

    /**
     * Returns whether or not the column PHP native type is a primitive numeric
     * type (aka an integer, a long, a float or a double).
     *
     * @see PropelTypes::isPhpPrimitiveNumericType()
     *
     * @return bool
     */
    public function isPhpPrimitiveNumericType()
    {
        return PropelTypes::isPhpPrimitiveNumericType($this->getPhpType());
    }

    /**
     * Returns whether or not the column PHP native type is an object.
     *
     * @see PropelTypes::isPhpObjectType()
     *
     * @return bool
     */
    public function isPhpObjectType()
    {
        return PropelTypes::isPhpObjectType($this->getPhpType());
    }

    /**
     * Returns an instance of PlatformInterface interface.
     *
     * @return \Propel\Generator\Platform\PlatformInterface|null
     */
    public function getPlatform()
    {
        return $this->parentTable->getPlatform();
    }

    /**
     * Returns whether or not this column has a platform adapter.
     *
     * @return bool
     */
    public function hasPlatform()
    {
        if ($this->parentTable === null) {
            return false;
        }

        return $this->parentTable->getPlatform() ? true : false;
    }

    /**
     * Clones the current object.
     *
     * @return void
     */
    public function __clone()
    {
        $this->referrers = [];
        if ($this->domain) {
            $this->domain = clone $this->domain;
        }
    }

    /**
     * Returns a generated PHP name.
     *
     * @param string $name
     * @param string $phpNamingMethod
     * @param string|null $namePrefix
     *
     * @return string
     */
    public static function generatePhpName($name, $phpNamingMethod = PhpNameGenerator::CONV_METHOD_CLEAN, $namePrefix = null)
    {
        return NameFactory::generateName(NameFactory::PHP_GENERATOR, [ $name, $phpNamingMethod, $namePrefix ]);
    }

    /**
     * Generates the singular form of a PHP name.
     *
     * @param string $phpname
     *
     * @return string
     */
    public static function generatePhpSingularName($phpname)
    {
        return rtrim($phpname, 's');
    }
}
