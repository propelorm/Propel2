<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

/**
 * Information about indices of a table.
 *
 * @author Jason van Zyl <vanzyl@apache.org>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Index extends MappingModel
{
    /**
     * @var string|null
     */
    protected $name;

    /**
     * The Table instance.
     *
     * @var \Propel\Generator\Model\Table|null
     */
    protected $table;

    /**
     * @var array<string>
     */
    protected $columns = [];

    /**
     * @var array<\Propel\Generator\Model\Column>
     */
    protected $columnObjects = [];

    /**
     * @var array<int>
     */
    protected $columnsSize = [];

    /**
     * @var bool
     */
    protected $autoNaming = false;

    /**
     * Creates a new Index instance.
     *
     * @param string|null $name Name of the index
     */
    public function __construct(?string $name = null)
    {
        if ($name !== null) {
            $this->setName($name);
        }
    }

    /**
     * Returns the uniqueness of this index.
     *
     * @return bool
     */
    public function isUnique(): bool
    {
        return false;
    }

    /**
     * Sets the index name.
     *
     * @param string|null $name
     *
     * @return void
     */
    public function setName(?string $name): void
    {
        $this->autoNaming = !$name; //if no name we activate autoNaming
        $this->name = $name;
    }

    /**
     * Returns the index name.
     *
     * @return string
     */
    public function getName(): string
    {
        $this->doNaming();

        if ($this->table && $this->table->getDatabase()) {
            return substr($this->name, 0, $this->table->getDatabase()->getMaxColumnNameLength());
        }

        return $this->name;
    }

    /**
     * @return void
     */
    protected function doNaming(): void
    {
        if ($this->name && !$this->autoNaming) {
            return;
        }
        $newName = sprintf('%s_', $this instanceof Unique ? 'u' : 'i');

        if ($this->columns) {
            $hash = [];
            $hash[] = implode(',', (array)$this->columns);
            $hash[] = implode(',', (array)$this->columnsSize);

            $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);
        } else {
            $newName .= 'no_columns';
        }

        if ($this->table) {
            $newName = $this->table->getCommonName() . '_' . $newName;
        }

        $this->name = $newName;
        $this->autoNaming = true;
    }

    /**
     * @return string
     */
    public function getFQName(): string
    {
        $table = $this->getTable();
        if (
            $table
            && $table->getDatabase()
            && ($table->getSchema() || $table->getDatabase()->getSchema())
            && $table->getDatabase()->getPlatform()
            && $table->getDatabase()->getPlatform()->supportsSchemas()
        ) {
            return ($table->getSchema() ?: $table->getDatabase()->getSchema()) . '.' . $this->getName();
        }

        return $this->getName();
    }

    /**
     * Sets the index parent Table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function setTable(Table $table): void
    {
        $this->table = $table;
    }

    /**
     * Returns the index parent table.
     *
     * @return \Propel\Generator\Model\Table|null
     */
    public function getTable(): ?Table
    {
        return $this->table;
    }

    /**
     * Returns the name of the index parent table.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->table->getName();
    }

    /**
     * Adds a new column to the index.
     *
     * @param \Propel\Generator\Model\Column|array $data Column or attributes from XML.
     *
     * @return void
     */
    public function addColumn($data): void
    {
        if ($data instanceof Column) {
            $column = $data;
            $this->columns[] = $column->getName();
            if ($column->getSize()) {
                $this->columnsSize[$column->getName()] = (int)$column->getSize();
            }
            $this->columnObjects[] = $column;
        } else {
            $this->columns[] = $name = $data ? $data['name'] : null;
            if (isset($data['size']) && $data['size'] > 0) {
                $this->columnsSize[$name] = $data['size'];
            }
            if ($this->getTable()) {
                $this->columnObjects[] = $this->getTable()->getColumn($name);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasColumn(string $name): bool
    {
        return in_array($name, $this->columns, true);
    }

    /**
     * Sets an array of columns to use for the index.
     *
     * @param array $columns array of array definitions $columns[]['name'] = 'columnName'
     *
     * @return void
     */
    public function setColumns(array $columns): void
    {
        $this->columns = [];
        $this->columnsSize = [];
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
    }

    /**
     * Returns whether there is a size for the specified column.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasColumnSize(string $name): bool
    {
        return isset($this->columnsSize[$name]);
    }

    /**
     * Returns the size for the specified column.
     *
     * @param string $name
     * @param bool $caseInsensitive
     *
     * @return int|null
     */
    public function getColumnSize(string $name, bool $caseInsensitive = false): ?int
    {
        if ($caseInsensitive) {
            foreach ($this->columnsSize as $forName => $size) {
                if (strcasecmp($forName, $name) === 0) {
                    return $size;
                }
            }

            return null;
        }

        return $this->columnsSize[$name] ?? null;
    }

    /**
     * Resets the columns sizes.
     *
     * This method is useful for generated indices for FKs.
     *
     * @return void
     */
    public function resetColumnsSize(): void
    {
        $this->columnsSize = [];
    }

    /**
     * Returns whether this index has a given column at a given position.
     *
     * @param int $pos Position in the column list
     * @param string $name Column name
     * @param int|null $size Optional size check
     * @param bool $caseInsensitive Whether the comparison is case insensitive (false by default)
     *
     * @return bool
     */
    public function hasColumnAtPosition(int $pos, string $name, ?int $size = null, bool $caseInsensitive = false): bool
    {
        if (!isset($this->columns[$pos])) {
            return false;
        }

        if ($caseInsensitive) {
            $test = strcasecmp($this->columns[$pos], $name) === 0;
        } else {
            $test = $this->columns[$pos] == $name;
        }

        if (!$test) {
            return false;
        }

        if ($this->getColumnSize($name, $caseInsensitive) != $size) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether the index has columns.
     *
     * @return bool
     */
    public function hasColumns(): bool
    {
        return count($this->columns) > 0;
    }

    /**
     * Returns the list of local columns.
     *
     * You should not edit this list.
     *
     * @return array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return void
     */
    protected function setupObject(): void
    {
        $this->setName($this->getAttribute('name'));
    }

    /**
     * @return array<\Propel\Generator\Model\Column>
     */
    public function getColumnObjects(): array
    {
        return $this->columnObjects;
    }

    /**
     * @param array<\Propel\Generator\Model\Column> $columnObjects
     *
     * @return void
     */
    public function setColumnObjects(array $columnObjects): void
    {
        $this->columnObjects = $columnObjects;
    }
}
