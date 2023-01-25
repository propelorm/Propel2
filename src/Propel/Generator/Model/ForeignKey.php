<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Exception\RuntimeException;

/**
 * A class for information about table foreign keys.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Fedor <fedor.karpelevitch@home.com>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Ulf Hermann <ulfhermann@kulturserver.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class ForeignKey extends MappingModel
{
    /**
     * These constants are the uppercase equivalents of the onDelete / onUpdate
     * values in the schema definition.
     *
     * @var string
     */
    public const NONE = ''; // No 'ON [ DELETE | UPDATE]' behavior

    /**
     * @var string
     */
    public const NOACTION = 'NO ACTION';

    /**
     * @var string
     */
    public const CASCADE = 'CASCADE';

    /**
     * @var string
     */
    public const RESTRICT = 'RESTRICT';

    /**
     * @var string
     */
    public const SETDEFAULT = 'SET DEFAULT';

    /**
     * @var string
     */
    public const SETNULL = 'SET NULL';

    /**
     * @var string
     */
    private $foreignTableCommonName;

    /**
     * @var string
     */
    private $foreignSchemaName;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $phpName;

    /**
     * @var string|null
     */
    private $refPhpName;

    /**
     * @var string
     */
    private $defaultJoin;

    /**
     * @var string
     */
    private $onUpdate = '';

    /**
     * @var string
     */
    private $onDelete = '';

    /**
     * @var \Propel\Generator\Model\Table
     */
    private $parentTable;

    /**
     * @var array<string>
     */
    private $localColumns = [];

    /**
     * @var array<string|null>
     */
    private $foreignColumns = [];

    /**
     * @var array<string|null>
     */
    private $localValues = [];

    /**
     * @var bool
     */
    private $skipSql = false;

    /**
     * @var string
     */
    private $interface;

    /**
     * @var bool
     */
    private $autoNaming = false;

    /**
     * Constructs a new ForeignKey object.
     *
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        if ($name !== null) {
            $this->setName($name);
        }

        $this->onUpdate = self::NONE;
        $this->onDelete = self::NONE;
    }

    /**
     * @return void
     */
    protected function setupObject(): void
    {
        $this->foreignTableCommonName = $this->parentTable->getDatabase()->getTablePrefix() . $this->getAttribute('foreignTable');
        $this->foreignSchemaName = $this->getAttribute('foreignSchema');

        $this->name = $this->getAttribute('name');
        $this->phpName = $this->getAttribute('phpName');
        $this->refPhpName = $this->getAttribute('refPhpName');
        $this->defaultJoin = $this->getAttribute('defaultJoin');
        $this->interface = $this->getAttribute('interface');
        $this->onUpdate = $this->normalizeFKey($this->getAttribute('onUpdate'));
        $this->onDelete = $this->normalizeFKey($this->getAttribute('onDelete'));
        $this->skipSql = $this->booleanValue($this->getAttribute('skipSql'));
    }

    /**
     * @return void
     */
    protected function doNaming(): void
    {
        if (!$this->name || $this->autoNaming) {
            $newName = 'fk_';

            $hash = [];
            $hash[] = $this->foreignSchemaName . '.' . $this->foreignTableCommonName;
            $hash[] = implode(',', $this->localColumns);
            $hash[] = implode(',', $this->foreignColumns);

            $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);

            if ($this->parentTable !== null) {
                $newName = $this->parentTable->getCommonName() . '_' . $newName;
            }

            $this->name = $newName;
            $this->autoNaming = true;
        }
    }

    /**
     * Returns the normalized input of onDelete and onUpdate behaviors.
     *
     * @param string|null $behavior
     * @param string|null $default
     *
     * @return string
     */
    public function normalizeFKey(?string $behavior, ?string $default = null): string
    {
        if ($behavior === null) {
            return $default ?: self::NONE;
        }

        $behavior = strtoupper($behavior);

        if ($behavior === 'NONE' || $behavior === self::NONE) {
            return $default ?: self::NONE;
        }

        if ($behavior === 'SETNULL') {
            return self::SETNULL;
        }

        if ($behavior === 'NOACTION') {
            return self::NOACTION;
        }

        return $behavior;
    }

    /**
     * Returns whether the onUpdate behavior is set.
     *
     * @return bool
     */
    public function hasOnUpdate(): bool
    {
        return $this->onUpdate !== self::NONE;
    }

    /**
     * Returns whether the onDelete behavior is set.
     *
     * @return bool
     */
    public function hasOnDelete(): bool
    {
        return $this->onDelete !== self::NONE;
    }

    /**
     * Returns true if $column is in our local columns list.
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @return bool
     */
    public function hasLocalColumn(Column $column): bool
    {
        return in_array($column, $this->getLocalColumnObjects(), true);
    }

    /**
     * Returns the onUpdate behavior.
     *
     * @return string
     */
    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }

    /**
     * Returns the normalized onUpdate behavior taking into account the default of the platform in case the behavior is implicit
     *
     * @return string|null
     */
    public function getOnUpdateWithDefault(): ?string
    {
        $rawBehavior = $this->getOnUpdate();
        $platform = $this->getPlatform();
        $defaultBehavior = ($platform) ? $platform->getDefaultForeignKeyOnUpdateBehavior() : null;

        return $this->normalizeFKey($rawBehavior, $defaultBehavior);
    }

    /**
     * Returns the onDelete behavior.
     *
     * @return string
     */
    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    /**
     * Returns the normalized onDelete behavior taking into account the default of the platform in case the behavior is implicit
     *
     * @return string|null
     */
    public function getOnDeleteWithDefault(): ?string
    {
        $rawBehavior = $this->getOnDelete();
        $platform = $this->getPlatform();
        $defaultBehavior = ($platform) ? $platform->getDefaultForeignKeyOnDeleteBehavior() : null;

        return $this->normalizeFKey($rawBehavior, $defaultBehavior);
    }

    /**
     * Sets the onDelete behavior.
     *
     * @param string|null $behavior
     *
     * @return void
     */
    public function setOnDelete(?string $behavior): void
    {
        $this->onDelete = $this->normalizeFKey($behavior);
    }

    /**
     * Sets the onUpdate behavior.
     *
     * @param string|null $behavior
     *
     * @return void
     */
    public function setOnUpdate(?string $behavior): void
    {
        $this->onUpdate = $this->normalizeFKey($behavior);
    }

    /**
     * Returns the foreign key name.
     *
     * @return string
     */
    public function getName(): string
    {
        $this->doNaming();

        return $this->name;
    }

    /**
     * Sets the foreign key name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->autoNaming = !$name; //if no name we activate autoNaming
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getInterface(): ?string
    {
        return $this->interface;
    }

    /**
     * @param string $interface
     *
     * @return void
     */
    public function setInterface(string $interface): void
    {
        $this->interface = $interface;
    }

    /**
     * Returns the phpName for this foreign key (if any).
     *
     * @return string|null
     */
    public function getPhpName(): ?string
    {
        return $this->phpName;
    }

    /**
     * Sets a phpName to use for this foreign key.
     *
     * @param string $name
     *
     * @return void
     */
    public function setPhpName(string $name): void
    {
        $this->phpName = $name;
    }

    /**
     * Returns the refPhpName for this foreign key (if any).
     *
     * @return string|null
     */
    public function getRefPhpName(): ?string
    {
        return $this->refPhpName;
    }

    /**
     * Sets a refPhpName to use for this foreign key.
     *
     * @param string $name
     *
     * @return void
     */
    public function setRefPhpName(string $name): void
    {
        $this->refPhpName = $name;
    }

    /**
     * Returns the default join strategy for this foreign key (if any).
     *
     * @return string|null
     */
    public function getDefaultJoin(): ?string
    {
        return $this->defaultJoin;
    }

    /**
     * Sets the default join strategy for this foreign key (if any).
     *
     * @param string $join
     *
     * @return void
     */
    public function setDefaultJoin(string $join): void
    {
        $this->defaultJoin = $join;
    }

    /**
     * Returns the PlatformInterface instance.
     *
     * @return \Propel\Generator\Platform\PlatformInterface|null
     */
    private function getPlatform(): ?PlatformInterface
    {
        return $this->parentTable->getPlatform();
    }

    /**
     * Returns the Database object of this Column.
     *
     * @return \Propel\Generator\Model\Database|null
     */
    public function getDatabase(): ?Database
    {
        return $this->parentTable->getDatabase();
    }

    /**
     * Returns the foreign table name of the FK.
     *
     * @return string|null
     */
    public function getForeignTableName(): ?string
    {
        $platform = $this->getPlatform();
        if ($this->foreignSchemaName && $platform && $platform->supportsSchemas()) {
            return $this->foreignSchemaName
                . $platform->getSchemaDelimiter()
                . $this->foreignTableCommonName;
        }

        $database = $this->getDatabase();
        $schema = $this->parentTable->guessSchemaName();
        if ($database && $schema && $platform && $platform->supportsSchemas()) {
            return $schema
                . $platform->getSchemaDelimiter()
                . $this->foreignTableCommonName;
        }

        return $this->foreignTableCommonName;
    }

    /**
     * Returns the foreign table name without schema.
     *
     * @return string|null
     */
    public function getForeignTableCommonName(): ?string
    {
        return $this->foreignTableCommonName;
    }

    /**
     * Sets the foreign table common name of the FK.
     *
     * @param string $tableName
     *
     * @return void
     */
    public function setForeignTableCommonName(string $tableName): void
    {
        $this->foreignTableCommonName = $tableName;
    }

    /**
     * Returns the resolved foreign Table model object.
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getForeignTable(): ?Table
    {
        $database = $this->parentTable->getDatabase();
        if ($database) {
            return $database->getTable($this->getForeignTableName());
        }

        return null;
    }

    /**
     * Returns the resolved foreign Table model object.
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getForeignTableOrFail(): Table
    {
        $database = $this->parentTable->getDatabaseOrFail();

        return $database->getTable($this->getForeignTableName());
    }

    /**
     * Returns the foreign schema name of the FK.
     *
     * @return string|null
     */
    public function getForeignSchemaName(): ?string
    {
        return $this->foreignSchemaName;
    }

    /**
     * Set the foreign schema name of the foreign key.
     *
     * @param string|null $schemaName
     *
     * @return void
     */
    public function setForeignSchemaName(?string $schemaName): void
    {
        $this->foreignSchemaName = $schemaName;
    }

    /**
     * Sets the parent Table of the foreign key.
     *
     * @param \Propel\Generator\Model\Table $parent
     *
     * @return void
     */
    public function setTable(Table $parent): void
    {
        $this->parentTable = $parent;
    }

    /**
     * Returns the parent Table of the foreign key.
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getTable(): Table
    {
        return $this->parentTable;
    }

    /**
     * Returns the name of the table the foreign key is in.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->parentTable->getName();
    }

    /**
     * Returns the name of the schema the foreign key is in.
     *
     * @return string|null
     */
    public function getSchemaName(): ?string
    {
        return $this->parentTable->getSchema();
    }

    /**
     * Adds a new reference entry to the foreign key.
     *
     * @param mixed $ref1 A Column object or an associative array or a string
     * @param mixed $ref2 A Column object or a single string name
     *
     * @return void
     */
    public function addReference($ref1, $ref2 = null): void
    {
        if (is_array($ref1)) {
            $this->localColumns[] = $ref1['local'] ?? null;
            $this->foreignColumns[] = $ref1['foreign'] ?? null;
            $this->localValues[] = $ref1['value'] ?? null;

            return;
        }

        if (is_string($ref1)) {
            $this->localColumns[] = $ref1;
            $this->foreignColumns[] = is_string($ref2) ? $ref2 : null;
            $this->localValues[] = null;

            return;
        }

        $local = null;
        if ($ref1 instanceof Column) {
            /** @var string $local */
            $local = $ref1->getName();
            $this->localColumns[] = $local;
        } else {
            $this->localValues[] = $local;
        }

        if ($ref2 instanceof Column) {
            $foreign = $ref2->getName();
            $this->foreignColumns[] = $foreign;
            $this->localValues[] = null;
        } elseif ($ref1 instanceof Column) {
            $this->foreignColumns[] = null;
            $this->localValues[] = $ref2;
        }
    }

    /**
     * Clears the references of this foreign key.
     *
     * @return void
     */
    public function clearReferences(): void
    {
        $this->localColumns = [];
        $this->foreignColumns = [];
        $this->localValues = [];
    }

    /**
     * Returns an array of local column names.
     *
     * @return array<string>
     */
    public function getLocalColumns(): array
    {
        return $this->localColumns;
    }

    /**
     * Returns an array of local column objects.
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getLocalColumnObjects(): array
    {
        $columns = [];
        foreach ($this->localColumns as $columnName) {
            /** @var \Propel\Generator\Model\Column $column */
            $column = $this->parentTable->getColumn($columnName);
            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Returns a local column name identified by a position.
     *
     * @param int $index
     *
     * @return string
     */
    public function getLocalColumnName(int $index = 0): string
    {
        return $this->localColumns[$index];
    }

    /**
     * Returns a local Column object identified by a position.
     *
     * @param int $index
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getLocalColumn(int $index = 0): ?Column
    {
        return $this->parentTable->getColumn($this->getLocalColumnName($index));
    }

    /**
     * @return array [[Column $leftColumn, $rightValueOrColumn], ..., ...]
     */
    public function getMapping(): array
    {
        $mapping = [];
        $size = count($this->localColumns);
        for ($i = 0; $i < $size; $i++) {
            $right = $this->foreignColumns[$i];
            if ($right) {
                $right = $this->getForeignTable()->getColumn($right);
            } else {
                $right = $this->localValues[$i];
            }
            $mapping[] = [$this->parentTable->getColumn($this->localColumns[$i]), $right];
        }

        return $mapping;
    }

    /**
     * @return array [[$leftValueOrColumn, Column $rightColumn], ..., ...]
     */
    public function getInverseMapping(): array
    {
        $mapping = $this->getMapping();
        foreach ($mapping as &$map) {
            $left = $map[0];
            $map[0] = $map[1];
            $map[1] = $left;
        }

        return $mapping;
    }

    /**
     * Returns an array of local and foreign column objects
     * mapped for this foreign key.
     *
     * @return array
     */
    public function getColumnObjectsMapping(): array
    {
        $mapping = [];
        $foreignTable = $this->getForeignTable();
        $size = count($this->localColumns);
        for ($i = 0; $i < $size; $i++) {
            $mapping[] = [
                'local' => $this->parentTable->getColumn($this->localColumns[$i]),
                'foreign' => $foreignTable->getColumn($this->foreignColumns[$i]),
                'value' => $this->localValues[$i],
            ];
        }

        return $mapping;
    }

    /**
     * Returns the foreign column name mapped to a specified local column.
     *
     * @param string $local
     *
     * @return string|null
     */
    public function getMappedForeignColumn(string $local): ?string
    {
        $index = array_search($local, $this->localColumns);

        return $this->foreignColumns[$index];
    }

    /**
     * Returns the local column name mapped to a specified foreign column.
     *
     * @param string $foreign
     *
     * @return string|null
     */
    public function getMappedLocalColumn(string $foreign): ?string
    {
        $index = array_search($foreign, $this->foreignColumns);

        return $this->localColumns[$index];
    }

    /**
     * Returns an array of foreign column names.
     *
     * @return array<string|null>
     */
    public function getForeignColumns(): array
    {
        return $this->foreignColumns;
    }

    /**
     * Returns an array of foreign column objects.
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getForeignColumnObjects(): array
    {
        $columns = [];
        $foreignTable = $this->getForeignTable();
        foreach ($this->foreignColumns as $columnName) {
            $column = $foreignTable->getColumn($columnName);
            if ($column !== null) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * Returns a foreign column name.
     *
     * @param int $index
     *
     * @return string|null
     */
    public function getForeignColumnName(int $index = 0): ?string
    {
        return $this->foreignColumns[$index];
    }

    /**
     * Returns a foreign column object.
     *
     * @param int $index
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getForeignColumn(int $index = 0): ?Column
    {
        return $this->getForeignTable()->getColumn($this->getForeignColumnName($index));
    }

    /**
     * Returns whether this foreign key uses only required local columns.
     *
     * @return bool
     */
    public function isLocalColumnsRequired(): bool
    {
        foreach ($this->localColumns as $columnName) {
            if (!$this->parentTable->getColumn($columnName)->isNotNull()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether this foreign key uses at least one required local column.
     *
     * @return bool
     */
    public function isAtLeastOneLocalColumnRequired(): bool
    {
        foreach ($this->localColumns as $columnName) {
            if ($this->parentTable->getColumn($columnName)->isNotNull()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this foreign key uses at least one required(notNull && no defaultValue) local primary key.
     *
     * @return bool
     */
    public function isAtLeastOneLocalPrimaryKeyIsRequired(): bool
    {
        foreach ($this->getLocalPrimaryKeys() as $pk) {
            if ($pk->isNotNull() && !$pk->hasDefaultValue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this foreign key is also the primary key of the foreign
     * table.
     *
     * @return bool Returns true if all columns inside this foreign key are primary keys of the foreign table
     */
    public function isForeignPrimaryKey(): bool
    {
        $foreignTable = $this->getForeignTable();

        $foreignPKCols = [];
        foreach ($foreignTable->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[] = $fPKCol->getName();
        }

        $foreignCols = [];
        foreach ($this->localColumns as $idx => $colName) {
            if ($this->foreignColumns[$idx]) {
                $foreignCols[] = $foreignTable->getColumn($this->foreignColumns[$idx])->getName();
            }
        }

        return ((count($foreignPKCols) === count($foreignCols))
            && !array_diff($foreignPKCols, $foreignCols));
    }

    /**
     * Returns whether this foreign key is not the primary key of the foreign
     * table.
     *
     * @return bool Returns true if all columns inside this foreign key are not primary keys of the foreign table
     */
    public function isForeignNonPrimaryKey(): bool
    {
        $foreignTable = $this->getForeignTable();

        $foreignPKCols = [];
        foreach ($foreignTable->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[] = $fPKCol->getName();
        }

        $foreignCols = [];
        foreach ($this->localColumns as $idx => $colName) {
            if ($this->foreignColumns[$idx]) {
                $foreignCols[] = $foreignTable->getColumn($this->foreignColumns[$idx])->getName();
            }
        }

        return (bool)array_diff($foreignCols, $foreignPKCols);
    }

    /**
     * Returns whether this foreign key relies on more than one
     * column binding.
     *
     * @return bool
     */
    public function isComposite(): bool
    {
        return count($this->localColumns) > 1;
    }

    /**
     * @param array $mapping
     *
     * @return array [[$localColumnName, $right, $compare], ...]
     */
    public function getNormalizedMap(array $mapping): array
    {
        $result = [];

        foreach ($mapping as $map) {
            [$left, $right] = $map;
            $item = [];
            $item[0] = $left instanceof Column ? ':' . $left->getName() : $left;
            $item[1] = $right instanceof Column ? ':' . $right->getName() : $right;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Whether this relation is a polymorphic association.
     *
     * At least one reference with a expression attribute set.
     *
     * @return bool
     */
    public function isPolymorphic(): bool
    {
        foreach ($this->localValues as $value) {
            if ($value !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array [[$localColumnName, $localValue], [.., ..], ...]
     */
    public function getLocalValues(): array
    {
        $map = [];

        foreach ($this->localColumns as $idx => $columnName) {
            if ($this->localValues[$idx]) {
                $map[] = [$columnName, $this->localValues[$idx]];
            }
        }

        return $map;
    }

    /**
     * Returns whether this foreign key is also the primary key of
     * the local table.
     *
     * @return bool True if all local columns are at the same time a primary key
     */
    public function isLocalPrimaryKey(): bool
    {
        $localPKCols = [];
        foreach ($this->parentTable->getPrimaryKey() as $lPKCol) {
            $localPKCols[] = $lPKCol->getName();
        }

        return count($localPKCols) === count($this->localColumns) && !array_diff($localPKCols, $this->localColumns);
    }

    /**
     * Sets whether this foreign key should have its creation SQL
     * generated.
     *
     * @param bool $skip
     *
     * @return void
     */
    public function setSkipSql(bool $skip): void
    {
        $this->skipSql = $skip;
    }

    /**
     * Returns whether the SQL generation must be skipped for this
     * foreign key.
     *
     * @return bool
     */
    public function isSkipSql(): bool
    {
        return $this->skipSql;
    }

    /**
     * Whether this foreign key is matched by an inverted foreign key (on foreign table).
     *
     * This is to prevent duplicate columns being generated for a 1:1 relationship that is represented
     * by foreign keys on both tables. I don't know if that's good practice ... but hell, why not
     * support it.
     *
     * @link http://propel.phpdb.org/trac/ticket/549
     *
     * @return bool
     */
    public function isMatchedByInverseFK(): bool
    {
        return (bool)$this->getInverseFK();
    }

    /**
     * @throws \Propel\Runtime\Exception\RuntimeException
     *
     * @return \Propel\Generator\Model\ForeignKey|null
     */
    public function getInverseFK(): ?self
    {
        $foreignTable = $this->getForeignTable();
        if (!$foreignTable) {
            throw new RuntimeException('No foreign table given');
        }

        $map = $this->getInverseMapping();

        foreach ($foreignTable->getForeignKeys() as $refFK) {
            $fkMap = $refFK->getMapping();
            // compares keys and values, but doesn't care about order, included check to make sure it's the same table (fixes #679)
            if (($refFK->getTableName() === $this->getTableName()) && ($map === $fkMap)) {
                return $refFK;
            }
        }

        return null;
    }

    /**
     * Returns the list of other foreign keys starting on the same table.
     * Used in many-to-many relationships.
     *
     * @return array<\Propel\Generator\Model\ForeignKey>
     */
    public function getOtherFks(): array
    {
        $fks = [];
        foreach ($this->parentTable->getForeignKeys() as $fk) {
            if ($fk !== $this) {
                $fks[] = $fk;
            }
        }

        return $fks;
    }

    /**
     * Returns all local columns which are also a primary key of the local table.
     *
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getLocalPrimaryKeys(): array
    {
        $cols = [];
        $localCols = $this->getLocalColumnObjects();

        foreach ($localCols as $localCol) {
            if ($localCol->isPrimaryKey()) {
                $cols[] = $localCol;
            }
        }

        return $cols;
    }

    /**
     * Whether at least one local column is also a primary key.
     *
     * @return bool True if there is at least one column that is a primary key
     */
    public function isAtLeastOneLocalPrimaryKey(): bool
    {
        $cols = $this->getLocalPrimaryKeys();

        return count($cols) !== 0;
    }
}
