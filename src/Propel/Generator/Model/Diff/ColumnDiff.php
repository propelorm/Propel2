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
    protected $changedProperties;
    protected $fromColumn;
    protected $toColumn;

    public function __construct()
    {
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
        $diff->setFromColumn($this->getToColumn());
        $diff->setToColumn($this->getFromColumn());

        // properties
        $changedProperties = [];
        foreach ($this->getChangedProperties() as $name => $propertyChange) {
            $changedProperties[$name] = array_reverse($propertyChange);
        }
        $diff->setChangedProperties($changedProperties);

        return $diff;
    }

    public function __toString()
    {
        $ret = '';
        $ret .= sprintf("      %s:\n", $this->getFromColumn()->getFullyQualifiedName());
        $ret .= "        modifiedProperties:\n";
        foreach ($this->getChangedProperties() as $key => $value) {
            $ret .= sprintf("          %s: %s\n", $key, json_encode($value));
        }

        return $ret;
    }
}
