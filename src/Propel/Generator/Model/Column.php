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
    /**
     * @var string
     */
    public const DEFAULT_TYPE = 'VARCHAR';

    /**
     * @var string
     */
    public const DEFAULT_VISIBILITY = 'public';

    /**
     * @var string
     */
    public const CONSTANT_PREFIX = 'COL_';

    /**
     * @var array<string>
     */
    public static $validVisibilities = [
        'public',
        'protected',
        'private',
    ];

    /**
     * @var string
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
     * @var array<string>
     */
    protected $valueSet = [];

    /**
     * Creates a new column and set the name.
     *
     * @param string $name The column's name
     * @param string|null $type The column's type
     * @param string|int|null $size The column's size
     */
    public function __construct(string $name, ?string $type = null, $size = null)
    {
        $this->setName($name);

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
    public function getTypeHint(): ?string
    {
        return $this->typeHint;
    }

    /**
     * @param string|null $typeHint
     *
     * @return void
     */
    public function setTypeHint(?string $typeHint): void
    {
        $this->typeHint = $typeHint;
    }

    /**
     * @param \Propel\Generator\Platform\PlatformInterface|null $platform
     *
     * @return \Propel\Generator\Model\Domain
     */
    protected function getDomainFromAttributes(?PlatformInterface $platform): Domain
    {
        $domainName = $this->getAttribute('domain');
        if ($domainName) {
             return $this->getDatabase()->getDomain($domainName);
        }
        $type = $this->getAttribute('type', static::DEFAULT_TYPE);
        $type = strtoupper($type);
        if ($platform) {
            return $platform->getDomainForType($type);
        }

        // no platform - probably during tests
        return new Domain($type);
    }

    /**
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return void
     */
    protected function setupObject(): void
    {
        try {
            $database = $this->getDatabase();
            $platform = ($this->hasPlatform()) ? $this->getPlatform() : null;

            $domain = $this->getDomain();
            $domainInAttributes = $this->getDomainFromAttributes($platform);
            $domain->copy($domainInAttributes);

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

            $this->namePrefix = $this->getAttribute('prefix', $this->parentTable->getAttribute('columnPrefix'));

            // Accessor visibility - no idea why this returns null, or the use case for that
            $visibility = $this->getMethodVisibility('accessorVisibility', 'defaultAccessorVisibility') ?: '';
            $this->setAccessorVisibility($visibility);

            // Mutator visibility
            $visibility = $this->getMethodVisibility('mutatorVisibility', 'defaultMutatorVisibility') ?: '';
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
                $e->getMessage(),
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
     * @return string|null
     */
    private function getMethodVisibility(string $attribute, string $parentAttribute): ?string
    {
        $database = $this->getDatabase();

        $visibility = $this->getAttribute(
            $attribute,
            $this->parentTable->getAttribute(
                $parentAttribute,
                $database->getAttribute(
                    $parentAttribute,
                    self::DEFAULT_VISIBILITY,
                ),
            ),
        );

        return $visibility;
    }

    /**
     * Returns the database object the current column is in.
     *
     * @return \Propel\Generator\Model\Database|null
     */
    private function getDatabase(): ?Database
    {
        return $this->parentTable->getDatabase();
    }

    /**
     * Gets domain for this column, creating a new empty domain object if none is set.
     *
     * @return \Propel\Generator\Model\Domain
     */
    public function getDomain(): Domain
    {
        if ($this->domain === null) {
            $this->domain = new Domain();
        }

        return $this->domain;
    }

    /**
     * Sets the domain for this column.
     *
     * @param \Propel\Generator\Model\Domain $domain
     *
     * @return void
     */
    public function setDomain(Domain $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * Returns the fully qualified column name (table.column).
     *
     * @return string
     */
    public function getFullyQualifiedName(): string
    {
        return $this->parentTable->getName() . '.' . strtoupper($this->getName());
    }

    /**
     * Returns the column name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the lowercased column name.
     *
     * @return string
     */
    public function getLowercasedName(): string
    {
        return strtolower($this->name);
    }

    /**
     * Returns the uppercased column name.
     *
     * @return string
     */
    public function getUppercasedName(): string
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
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns whether the column name is plural.
     *
     * @return bool
     */
    public function isNamePlural(): bool
    {
        return $this->getSingularName() !== $this->name;
    }

    /**
     * Returns the column singular name.
     *
     * @return string
     */
    public function getSingularName(): string
    {
        if ($this->getAttribute('phpSingularName')) {
            return $this->getAttribute('phpSingularName');
        }

        return rtrim($this->name, 's');
    }

    /**
     * Returns the column description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
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
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Returns the name to use in PHP sources. It will set & return
     * a self-generated phpName from its name if its not already set.
     *
     * @return string
     */
    public function getPhpName(): string
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
     * @return string|null
     */
    public function getPhpSingularName(): ?string
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
    public function setPhpName(?string $phpName = null): void
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
    public function setPhpSingularName(?string $phpSingularName = null): void
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
    public function getCamelCaseName(): string
    {
        return lcfirst($this->getPhpName());
    }

    /**
     * Returns the accessor methods visibility of this column / attribute.
     *
     * @return string
     */
    public function getAccessorVisibility(): string
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
    public function setAccessorVisibility(string $visibility): void
    {
        $visibility = strtolower($visibility);
        if (!in_array($visibility, self::$validVisibilities, true)) {
            $visibility = self::DEFAULT_VISIBILITY;
        }

        $this->accessorVisibility = $visibility;
    }

    /**
     * Returns the mutator methods visibility for this current column.
     *
     * @return string
     */
    public function getMutatorVisibility(): string
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
    public function setMutatorVisibility(string $visibility): void
    {
        $visibility = strtolower($visibility);
        if (!in_array($visibility, self::$validVisibilities, true)) {
            $visibility = self::DEFAULT_VISIBILITY;
        }

        $this->mutatorVisibility = $visibility;
    }

    /**
     * Returns the full column constant name (e.g. TableMapName::COL_COLUMN_NAME).
     *
     * @return string A column constant name for insertion into PHP code
     */
    public function getFQConstantName(): string
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
    public function getConstantName(): string
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
     * @return string|null
     */
    public function getTableMapName(): ?string
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
    public function setTableMapName(string $name): void
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
    public function getPhpType(): string
    {
        return $this->phpType ?: $this->getPhpNative();
    }

    /**
     * Returns the location of this column within the table (one-based).
     *
     * @return int|null
     */
    public function getPosition(): ?int
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
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * Sets the parent table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function setTable(Table $table): void
    {
        $this->parentTable = $table;
    }

    /**
     * Returns the parent table.
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getTable(): ?Table
    {
        return $this->parentTable;
    }

    /**
     * Returns the parent table name.
     *
     * @return string
     */
    public function getTableName(): string
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
    public function addInheritance($inheritance): Inheritance
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
    public function getInheritanceType(): ?string
    {
        return $this->inheritanceType;
    }

    /**
     * Returns the inheritance list.
     *
     * @return array|null
     */
    public function getInheritanceList(): ?array
    {
        return $this->inheritanceList;
    }

    /**
     * Returns the inheritance definitions.
     *
     * @return array|null
     */
    public function getChildren(): ?array
    {
        return $this->inheritanceList;
    }

    /**
     * Returns whether this column is a normal property or specifies a
     * the classes that are represented in the table containing this column.
     *
     * @return bool
     */
    public function isInheritance(): bool
    {
        return $this->isInheritance;
    }

    /**
     * Returns whether possible classes have been enumerated in the
     * schema file.
     *
     * @return bool
     */
    public function isEnumeratedClasses(): bool
    {
        return $this->isEnumeratedClasses;
    }

    /**
     * Returns whether the column is not null.
     *
     * @return bool
     */
    public function isNotNull(): bool
    {
        return $this->isNotNull;
    }

    /**
     * Sets whether the column is not null.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setNotNull(bool $flag): void
    {
        $this->isNotNull = $flag;
    }

    /**
     * Returns NOT NULL string for this column.
     *
     * @return string
     */
    public function getNotNullString(): string
    {
        return $this->parentTable->getPlatform()->getNullString($this->isNotNull);
    }

    /**
     * Sets whether the column is used as the primary string.
     *
     * The primary string is the value used by default in the magic
     * __toString method of an active record object.
     *
     * @param bool $isPrimaryString
     *
     * @return void
     */
    public function setPrimaryString(bool $isPrimaryString): void
    {
        $this->isPrimaryString = $isPrimaryString;
    }

    /**
     * Returns true if the column is the primary string (used for the magic
     * __toString() method).
     *
     * @return bool
     */
    public function isPrimaryString(): bool
    {
        return $this->isPrimaryString;
    }

    /**
     * Sets whether the column is a primary key.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setPrimaryKey(bool $flag): void
    {
        $this->isPrimaryKey = $flag;
    }

    /**
     * Returns whether the column is the primary key.
     *
     * @return bool
     */
    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    /**
     * Sets whether the column is a node key of a tree.
     *
     * @param bool $isNodeKey
     *
     * @return void
     */
    public function setNodeKey(bool $isNodeKey): void
    {
        $this->isNodeKey = $isNodeKey;
    }

    /**
     * Returns whether the column is a node key of a tree.
     *
     * @return bool
     */
    public function isNodeKey(): bool
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
    public function setNodeKeySep(string $sep): void
    {
        $this->nodeKeySep = $sep;
    }

    /**
     * Returns the node key column separator for a tree.
     *
     * @return string
     */
    public function getNodeKeySep(): string
    {
        return $this->nodeKeySep;
    }

    /**
     * Sets whether the column is the nested set left key of a tree.
     *
     * @param bool $isNestedSetLeftKey
     *
     * @return void
     */
    public function setNestedSetLeftKey(bool $isNestedSetLeftKey): void
    {
        $this->isNestedSetLeftKey = $isNestedSetLeftKey;
    }

    /**
     * Returns whether the column is a nested set key of a tree.
     *
     * @return bool
     */
    public function isNestedSetLeftKey(): bool
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
    public function setNestedSetRightKey(bool $isNestedSetRightKey): void
    {
        $this->isNestedSetRightKey = $isNestedSetRightKey;
    }

    /**
     * Return whether the column is a nested set right key of a tree.
     *
     * @return bool
     */
    public function isNestedSetRightKey(): bool
    {
        return $this->isNestedSetRightKey;
    }

    /**
     * Sets whether the column is the scope key of a tree.
     *
     * @param bool $isTreeScopeKey
     *
     * @return void
     */
    public function setTreeScopeKey(bool $isTreeScopeKey): void
    {
        $this->isTreeScopeKey = $isTreeScopeKey;
    }

    /**
     * Returns whether the column is a scope key of a tree.
     *
     * @return bool
     */
    public function isTreeScopeKey(): bool
    {
        return $this->isTreeScopeKey;
    }

    /**
     * Returns whether the column must have a unique index.
     *
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * Returns true if the column requires a transaction in PostGreSQL.
     *
     * @return bool
     */
    public function requiresTransactionInPostgres(): bool
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * Returns whether this column is a foreign key.
     *
     * @return bool
     */
    public function isForeignKey(): bool
    {
        return count($this->getForeignKeys()) > 0;
    }

    /**
     * Returns whether this column is part of more than one foreign key.
     *
     * @return bool
     */
    public function hasMultipleFK(): bool
    {
        return count($this->getForeignKeys()) > 1;
    }

    /**
     * Returns the foreign key objects for this column.
     *
     * Only if it is a foreign key or part of a foreign key.
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getForeignKeys(): array
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
    public function addReferrer(ForeignKey $fk): void
    {
        $this->referrers[] = $fk;
    }

    /**
     * Returns the list of references to this column.
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getReferrers(): array
    {
        return $this->referrers;
    }

    /**
     * Returns whether this column has referers.
     *
     * @return bool
     */
    public function hasReferrers(): bool
    {
        return count($this->referrers) > 0;
    }

    /**
     * Returns whether this column has a specific referrer for a
     * specific foreign key object.
     *
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return bool
     */
    public function hasReferrer(ForeignKey $fk): bool
    {
        return $this->referrers && in_array($fk, $this->referrers, true);
    }

    /**
     * Clears all referrers.
     *
     * @return void
     */
    public function clearReferrers(): void
    {
        $this->referrers = [];
    }

    /**
     * Clears all inheritance children.
     *
     * @return void
     */
    public function clearInheritanceList(): void
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
    public function setDomainForType(string $mappingType): void
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
    public function setType(string $mappingType): void
    {
        $this->getDomain()->setType($mappingType);

        if (in_array($mappingType, [PropelTypes::VARBINARY, PropelTypes::LONGVARBINARY, PropelTypes::BLOB], true)) {
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
    public function getType(): string
    {
        return $this->getDomain()->getType();
    }

    /**
     * Returns the SQL type as a string.
     *
     * @see Domain::getSqlType()
     *
     * @return string
     */
    public function getSqlType(): string
    {
        return $this->getDomain()->getSqlType();
    }

    /**
     * Returns the column PDO type integer for this column's mapping type.
     *
     * @return int
     */
    public function getPDOType(): int
    {
        return PropelTypes::getPDOType($this->getType());
    }

    /**
     * @param \Propel\Generator\Platform\PlatformInterface|null $platform
     *
     * @return bool
     */
    public function isDefaultSqlType(?PlatformInterface $platform = null): bool
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
     * Returns whether this column is a blob/lob type.
     *
     * @return bool
     */
    public function isLobType(): bool
    {
        return PropelTypes::isLobType($this->getType());
    }

    /**
     * Returns whether this column is a text type.
     *
     * @return bool
     */
    public function isTextType(): bool
    {
        return PropelTypes::isTextType($this->getType());
    }

    /**
     * Returns whether this column is a numeric type.
     *
     * @return bool
     */
    public function isNumericType(): bool
    {
        return PropelTypes::isNumericType($this->getType());
    }

    /**
     * Returns whether this column is a boolean type.
     *
     * @return bool
     */
    public function isBooleanType(): bool
    {
        return PropelTypes::isBooleanType($this->getType());
    }

    /**
     * Returns whether this column is a temporal type.
     *
     * @return bool
     */
    public function isTemporalType(): bool
    {
        return PropelTypes::isTemporalType($this->getType());
    }

    /**
     * Returns whether this column is a uuid type.
     *
     * @return bool
     */
    public function isUuidType(): bool
    {
        return PropelTypes::isUuidType($this->getType());
    }

    /**
     * Returns whether this column is a uuid bin type.
     *
     * @return bool
     */
    public function isUuidBinaryType(): bool
    {
        return $this->getType() === PropelTypes::UUID_BINARY;
    }

    /**
     * Returns whether the column is an array column.
     *
     * @return bool
     */
    public function isPhpArrayType(): bool
    {
        return PropelTypes::isPhpArrayType($this->getType());
    }

    /**
     * Returns whether this column is an ENUM or SET column.
     *
     * @return bool
     */
    public function isValueSetType(): bool
    {
        return ($this->isEnumType() || $this->isSetType());
    }

    /**
     * Returns whether this column is an ENUM column.
     *
     * @return bool
     */
    public function isEnumType(): bool
    {
        return $this->getType() === PropelTypes::ENUM;
    }

    /**
     * Returns whether this column is a SET column.
     *
     * @return bool
     */
    public function isSetType(): bool
    {
        return $this->getType() === PropelTypes::SET;
    }

    /**
     * Sets the list of possible values for an ENUM or SET column.
     *
     * @param array<string>|string $valueSet
     *
     * @return void
     */
    public function setValueSet($valueSet): void
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
     * @return array<string>
     */
    public function getValueSet(): array
    {
        return $this->valueSet;
    }

    /**
     * Returns the column size.
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->domain ? $this->domain->getSize() : null;
    }

    /**
     * Sets the column size.
     *
     * @param int|null $size
     *
     * @return void
     */
    public function setSize(?int $size): void
    {
        $this->domain->setSize($size);
    }

    /**
     * Returns the column scale.
     *
     * @return int|null
     */
    public function getScale(): ?int
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
    public function setScale(int $scale): void
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
    public function getSizeDefinition(): string
    {
        return $this->domain->getSizeDefinition();
    }

    /**
     * Returns true if this table has a default value (and which is not NULL).
     *
     * @return bool
     */
    public function hasDefaultValue(): bool
    {
        return $this->getDefaultValue() !== null;
    }

    /**
     * Returns a string that will give this column a default value in PHP.
     *
     * @return string
     */
    public function getDefaultValueString(): string
    {
        $defaultValue = $this->getDefaultValue();

        if ($defaultValue === null) {
            return 'null';
        }

        if ($this->isNumericType()) {
            return (string)$defaultValue->getValue();
        }

        if ($this->isTextType() || $this->getDefaultValue()->isExpression()) {
            return sprintf("'%s'", str_replace("'", "\'", (string)$defaultValue->getValue()));
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
    public function setDefaultValue($defaultValue): void
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
    public function getDefaultValue(): ?ColumnDefaultValue
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
    public function isAutoIncrement(): bool
    {
        return $this->isAutoIncrement;
    }

    /**
     * Return whether the column has to be lazy loaded.
     *
     * For example, if a runtime query on the table doesn't hydrate this column
     * but a getter does.
     *
     * @return bool
     */
    public function isLazyLoad(): bool
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
    public function getAutoIncrementString(): string
    {
        if ($this->isAutoIncrement() && $this->parentTable->getIdMethod() === IdMethod::NATIVE) {
            return $this->getPlatform()->getAutoIncrement();
        }

        if ($this->isAutoIncrement()) {
            throw new EngineException(sprintf(
                'You have specified autoIncrement for column "%s", but you have not specified idMethod="native" for table "%s".',
                $this->name,
                $this->parentTable->getName(),
            ));
        }

        return '';
    }

    /**
     * Sets whether this column is an auto incremented value.
     *
     * Use isAutoIncrement() to find out if it is set or not.
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setAutoIncrement(bool $flag): void
    {
        $this->isAutoIncrement = $flag;
    }

    /**
     * Returns a string representation of the native PHP type which corresponds
     * to the Propel type of this column. Used in the generation of Base
     * objects.
     *
     * @return string
     */
    public function getPhpNative(): string
    {
        return PropelTypes::getPhpNative($this->getType());
    }

    /**
     * Returns whether the column PHP native type is primitive type (aka
     * a boolean, an integer, a long, a float, a double or a string).
     *
     * @see PropelTypes::isPhpPrimitiveType()
     *
     * @return bool
     */
    public function isPhpPrimitiveType(): bool
    {
        return PropelTypes::isPhpPrimitiveType($this->getPhpType());
    }

    /**
     * Returns whether the column PHP native type is a primitive numeric
     * type (aka an integer, a long, a float or a double).
     *
     * @see PropelTypes::isPhpPrimitiveNumericType()
     *
     * @return bool
     */
    public function isPhpPrimitiveNumericType(): bool
    {
        return PropelTypes::isPhpPrimitiveNumericType($this->getPhpType());
    }

    /**
     * Returns whether the column PHP native type is an object.
     *
     * @see PropelTypes::isPhpObjectType()
     *
     * @return bool
     */
    public function isPhpObjectType(): bool
    {
        return PropelTypes::isPhpObjectType($this->getPhpType());
    }

    /**
     * Returns an instance of PlatformInterface interface.
     *
     * @return \Propel\Generator\Platform\PlatformInterface|null
     */
    public function getPlatform(): ?PlatformInterface
    {
        return $this->parentTable->getPlatform();
    }

    /**
     * Returns whether this column has a platform adapter.
     *
     * @return bool
     */
    public function hasPlatform(): bool
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
     * @param string|null $phpNamingMethod
     * @param string|null $namePrefix
     *
     * @return string
     */
    public static function generatePhpName(string $name, ?string $phpNamingMethod = null, ?string $namePrefix = null): string
    {
        if ($phpNamingMethod === null) {
            $phpNamingMethod = PhpNameGenerator::CONV_METHOD_CLEAN;
        }

        return NameFactory::generateName(NameFactory::PHP_GENERATOR, [$name, $phpNamingMethod, (string)$namePrefix]);
    }

    /**
     * Generates the singular form of a PHP name.
     *
     * @param string $phpName
     *
     * @return string
     */
    public static function generatePhpSingularName(string $phpName): string
    {
        return rtrim($phpName, 's');
    }

    /**
     * Checks if xml attributes from schema.xml matches expected content declaration.
     *
     * @param string $content
     *
     * @return bool
     */
    public function isContent(string $content): bool
    {
        $contentAttribute = $this->getAttribute('content');

        return $contentAttribute && strtoupper($contentAttribute) === strtoupper($content);
    }
}
