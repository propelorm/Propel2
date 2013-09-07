<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Column;

/**
 * Value object for storing Column object diffs.
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class ColumnDiff
{
    /**
     * An associative array of modified properties.
     *
     * @var array
     */
    protected $changedProperties;

    /**
     * The original column definition.
     *
     * @var Column
     */
    protected $fromColumn;

    /**
     * The modified column definition.
     *
     * @var Column
     */
    protected $toColumn;

    /**
     * Constructor.
     *
     * @param Column $fromColumn The original column
     * @param Column $toColumn   The modified column
     */
    public function __construct(Column $fromColumn = null, Column $toColumn = null)
    {
        if (null !== $fromColumn) {
            $this->setFromColumn($fromColumn);
        }

        if (null !== $toColumn) {
            $this->setToColumn($toColumn);
        }

        $this->changedProperties = [];
    }

    /**
     * Sets for the changed properties.
     *
     * @param array $properties
     */
    public function setChangedProperties($properties)
    {
        $this->changedProperties = $properties;
    }

    /**
     * Returns the changed properties.
     *
     * @return array
     */
    public function getChangedProperties()
    {
        return $this->changedProperties;
    }

    /**
     * Sets the fromColumn property.
     *
     * @param Column $fromColumn
     */
    public function setFromColumn(Column $fromColumn)
    {
        $this->fromColumn = $fromColumn;
    }

    /**
     * Returns the fromColumn property.
     *
     * @return Column
     */
    public function getFromColumn()
    {
        return $this->fromColumn;
    }

    /**
     * Sets the toColumn property.
     *
     * @param Column $toColumn
     */
    public function setToColumn(Column $toColumn)
    {
        $this->toColumn = $toColumn;
    }

    /**
     * Returns the toColumn property.
     *
     * @return Column
     */
    public function getToColumn()
    {
        return $this->toColumn;
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return ColumnDiff
     */
    public function getReverseDiff()
    {
        $diff = new self();

        // columns
        $diff->setFromColumn($this->toColumn);
        $diff->setToColumn($this->fromColumn);

        // properties
        $changedProperties = [];
        foreach ($this->changedProperties as $name => $propertyChange) {
            $changedProperties[$name] = array_reverse($propertyChange);
        }
        $diff->setChangedProperties($changedProperties);

        return $diff;
    }

    /**
     * Returns the string representation of the difference.
     *
     * @return string
     */
    public function __toString()
    {
        $ret = '';
        $ret .= sprintf("      %s:\n", $this->fromColumn->getFullyQualifiedName());
        $ret .= "        modifiedProperties:\n";
        foreach ($this->changedProperties as $key => $value) {
            $ret .= sprintf("          %s: %s\n", $key, json_encode($value));
        }

        return $ret;
    }
}
