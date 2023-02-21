<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Map;

use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Map\Exception\ForeignKeyNotFoundException;

/**
 * ColumnMap is used to model a column of a table in a database.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime. These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 */
class ColumnMap
{
    /**
     * Propel type of the column
     *
     * @var string
     */
    protected $type;

    /**
     * Size of the column
     *
     * @var int
     */
    protected $size = 0;

    /**
     * Is it a primary key?
     *
     * @var bool
     */
    protected $pk = false;

    /**
     * Is null value allowed?
     *
     * @var bool
     */
    protected $notNull = false;

    /**
     * The default value for this column
     *
     * @var string|bool|null
     */
    protected $defaultValue;

    /**
     * Name of the table that this column is related to
     *
     * @var string
     */
    protected $relatedTableName = '';

    /**
     * Name of the column that this column is related to
     *
     * @var string
     */
    protected $relatedColumnName = '';

    /**
     * The TableMap for this column
     *
     * @var \Propel\Runtime\Map\TableMap
     */
    protected $table;

    /**
     * The name of the column
     *
     * @var string
     */
    protected $columnName;

    /**
     * The php name of the column
     *
     * @var string
     */
    protected $phpName;

    /**
     * The allowed values for an ENUM or SET column
     *
     * @var array
     */
    protected $valueSet = [];

    /**
     * Is this a primaryString column?
     *
     * @var bool
     */
    protected $isPkString = false;

    /**
     * @param string $name The name of the column.
     * @param \Propel\Runtime\Map\TableMap $containingTable TableMap of the table this column is in.
     * @param string $phpName The php name of the column.
     * @param string $type A string specifying the Propel type.
     */
    public function __construct(string $name, TableMap $containingTable, string $phpName, string $type)
    {
        $this->columnName = $name;
        $this->table = $containingTable;
        $this->phpName = $phpName;
        $this->type = $type;
    }

    /**
     * Get the name of a column.
     *
     * @return string A String with the column name.
     */
    public function getName(): string
    {
        return $this->columnName;
    }

    /**
     * Get the table map this column belongs to.
     *
     * @return \Propel\Runtime\Map\TableMap
     */
    public function getTable(): TableMap
    {
        return $this->table;
    }

    /**
     * Get the name of the table this column is in.
     *
     * @return string|null A String with the table name.
     */
    public function getTableName(): ?string
    {
        return $this->table->getName();
    }

    /**
     * Get the table name + column name.
     *
     * @return string A String with the full column name.
     */
    public function getFullyQualifiedName(): string
    {
        return $this->getTableName() . '.' . $this->columnName;
    }

    /**
     * Set the php name of this column.
     *
     * @param string $phpName A string representing the PHP name.
     *
     * @return void
     */
    public function setPhpName(string $phpName): void
    {
        $this->phpName = $phpName;
    }

    /**
     * Get the name of a column.
     *
     * @return string A String with the column name.
     */
    public function getPhpName(): string
    {
        return $this->phpName;
    }

    /**
     * Set the Propel type of this column.
     *
     * @param string $type A string representing the Propel type (e.g. PropelTypes::DATE).
     *
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the Propel type of this column.
     *
     * @return string A string representing the Propel type (e.g. PropelTypes::DATE).
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the PDO type of this column.
     *
     * @return int The PDO::PARAM_* value
     */
    public function getPdoType(): int
    {
        return PropelTypes::getPdoType($this->type);
    }

    /**
     * Whether this is a BLOB, LONGVARBINARY, or VARBINARY.
     *
     * @return bool
     */
    public function isLob(): bool
    {
        return in_array($this->type, [
            PropelTypes::BLOB,
            PropelTypes::VARBINARY,
            PropelTypes::LONGVARBINARY,
        ], true);
    }

    /**
     * Whether this is a DATE/TIME/TIMESTAMP column.
     *
     * @return bool
     */
    public function isTemporal(): bool
    {
        return in_array($this->type, [
            PropelTypes::TIMESTAMP,
            PropelTypes::DATE,
            PropelTypes::DATETIME,
            PropelTypes::TIME,
            PropelTypes::BU_DATE,
            PropelTypes::BU_TIMESTAMP,
        ], true);
    }

    /**
     * Whether this column is numeric (int, decimal, bigint etc).
     *
     * @return bool
     */
    public function isNumeric(): bool
    {
        return in_array($this->type, [
            PropelTypes::NUMERIC,
            PropelTypes::DECIMAL,
            PropelTypes::TINYINT,
            PropelTypes::SMALLINT,
            PropelTypes::INTEGER,
            PropelTypes::BIGINT,
            PropelTypes::REAL,
            PropelTypes::FLOAT,
            PropelTypes::DOUBLE,
        ], true);
    }

    /**
     * Whether this column is of type set.
     *
     * @return bool
     */
    public function isSetType(): bool
    {
        return $this->type === PropelTypes::SET;
    }

    /**
     * Whether this column is a text column (varchar, char, longvarchar).
     *
     * @return bool
     */
    public function isText(): bool
    {
        return in_array($this->type, [
            PropelTypes::VARCHAR,
            PropelTypes::LONGVARCHAR,
            PropelTypes::CHAR,
        ], true);
    }

    /**
     * Whether this column contains UUIDs.
     *
     * @return bool
     */
    public function isUuid(): bool
    {
        return PropelTypes::isUuidType($this->type);
    }

    /**
     * Set the size of this column.
     *
     * @param int|null $size An int specifying the size.
     *
     * @return void
     */
    public function setSize(?int $size): void
    {
        $this->size = (int)$size;
    }

    /**
     * Get the size of this column.
     *
     * @return int An int specifying the size.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Set if this column is a primary key or not.
     *
     * @param bool $pk True if column is a primary key.
     *
     * @return void
     */
    public function setPrimaryKey(bool $pk): void
    {
        $this->pk = $pk;
    }

    /**
     * Is this column a primary key?
     *
     * @return bool True if column is a primary key.
     */
    public function isPrimaryKey(): bool
    {
        return $this->pk;
    }

    /**
     * Set if this column may be null.
     *
     * @param bool $nn True if column may be null.
     *
     * @return void
     */
    public function setNotNull(bool $nn): void
    {
        $this->notNull = $nn;
    }

    /**
     * Is null value allowed ?
     *
     * @return bool True if column may not be null.
     */
    public function isNotNull(): bool
    {
        return $this->notNull || $this->isPrimaryKey();
    }

    /**
     * Sets the default value for this column.
     *
     * @param string|bool|null $defaultValue the default value for the column
     *
     * @return void
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Gets the default value for this column.
     *
     * @return string|bool|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set the foreign key for this column.
     *
     * @param string $tableName The name of the table that is foreign.
     * @param string $columnName The name of the column that is foreign.
     *
     * @return void
     */
    public function setForeignKey(string $tableName, string $columnName): void
    {
        if ($tableName && $columnName) {
            $this->relatedTableName = $tableName;
            $this->relatedColumnName = $columnName;
        } else {
            // @TODO to remove because it seems already done by default!
            $this->relatedTableName = '';
            $this->relatedColumnName = '';
        }
    }

    /**
     * Is this column a foreign key?
     *
     * @return bool True if column is a foreign key.
     */
    public function isForeignKey(): bool
    {
        return (bool)$this->relatedTableName;
    }

    /**
     * Get the RelationMap object for this foreign key
     *
     * @return \Propel\Runtime\Map\RelationMap|null
     */
    public function getRelation(): ?RelationMap
    {
        if (!$this->relatedTableName) {
            return null;
        }

        foreach ($this->getTable()->getRelations() as $relation) {
            if ($relation->getType() === RelationMap::MANY_TO_ONE) {
                if (
                    $relation->getForeignTable()->getName() === $this->getRelatedTableName()
                    && array_key_exists($this->getFullyQualifiedName(), $relation->getColumnMappings())
                ) {
                    return $relation;
                }
            }
        }

        return null;
    }

    /**
     * Get the table.column that this column is related to.
     *
     * @return string A String with the full name for the related column.
     */
    public function getRelatedName(): string
    {
        return $this->relatedTableName . '.' . $this->relatedColumnName;
    }

    /**
     * Get the table name that this column is related to.
     *
     * @return string A String with the name for the related table.
     */
    public function getRelatedTableName(): string
    {
        return $this->relatedTableName;
    }

    /**
     * Get the column name that this column is related to.
     *
     * @return string A String with the name for the related column.
     */
    public function getRelatedColumnName(): string
    {
        return $this->relatedColumnName;
    }

    /**
     * Get the TableMap object that this column is related to.
     *
     * @throws \Propel\Runtime\Map\Exception\ForeignKeyNotFoundException when called on a column with no foreign key
     *
     * @return \Propel\Runtime\Map\TableMap The related TableMap object
     */
    public function getRelatedTable(): TableMap
    {
        if (!$this->relatedTableName) {
            throw new ForeignKeyNotFoundException(sprintf('Cannot fetch RelatedTable for column with no foreign key: %s.', $this->columnName));
        }

        return $this->table->getDatabaseMap()->getTable($this->relatedTableName);
    }

    /**
     * Get the TableMap object that this column is related to.
     *
     * @return self The related ColumnMap object
     */
    public function getRelatedColumn(): self
    {
        return $this->getRelatedTable()->getColumn($this->relatedColumnName);
    }

    /**
     * Set the valueSet of this column (only valid for ENUM and SET columns).
     *
     * @param array $values A list of allowed values
     *
     * @return void
     */
    public function setValueSet(array $values): void
    {
        $this->valueSet = $values;
    }

    /**
     * Get the valueSet of this column (only valid for ENUM and SET columns).
     *
     * @return array A list of allowed values
     */
    public function getValueSet(): array
    {
        return $this->valueSet;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isInValueSet($value): bool
    {
        return in_array($value, $this->valueSet);
    }

    /**
     * @param mixed $value
     *
     * @return string|int|false
     */
    public function getValueSetKey($value)
    {
        return array_search($value, $this->valueSet);
    }

    /**
     * Performs DB-specific ignore case, but only if the column type necessitates it.
     *
     * @param string $str The expression we want to apply the ignore case formatting to (e.g. the column name).
     * @param \Propel\Runtime\Adapter\AdapterInterface $db
     *
     * @return string
     */
    public function ignoreCase(string $str, AdapterInterface $db): string
    {
        if ($this->isText()) {
            /** @phpstan-var \Propel\Runtime\Adapter\SqlAdapterInterface $db */
            return $db->ignoreCase($str);
        }

        return $str;
    }

    /**
     * Normalizes the column name, removing table prefix and uppercasing.
     *
     * article.first_name becomes FIRST_NAME
     *
     * @param string $name
     *
     * @return string Normalized column name.
     */
    public static function normalizeName(string $name): string
    {
        $pos = strrpos($name, '.');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
            $name = trim($name, " \t\n\r\0\x0B`'()\"[]~!-{}%^&.");
        }

        return strtoupper($name);
    }

    /**
     * Set this column to be a primaryString column.
     *
     * @param bool $pkString
     *
     * @return void
     */
    public function setPrimaryString(bool $pkString): void
    {
        $this->isPkString = $pkString;
    }

    /**
     * Is this column a primaryString column?
     *
     * @return bool True, if this column is the primaryString column.
     */
    public function isPrimaryString(): bool
    {
        return $this->isPkString;
    }
}
