<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    protected $changedProperties = [];

    /**
     * The original column definition.
     *
     * @var \Propel\Generator\Model\Column|null
     */
    protected $fromColumn;

    /**
     * The modified column definition.
     *
     * @var \Propel\Generator\Model\Column|null
     */
    protected $toColumn;

    /**
     * Constructor.
     *
     * @param \Propel\Generator\Model\Column|null $fromColumn The original column
     * @param \Propel\Generator\Model\Column|null $toColumn The modified column
     */
    public function __construct(?Column $fromColumn = null, ?Column $toColumn = null)
    {
        if ($fromColumn !== null) {
            $this->setFromColumn($fromColumn);
        }

        if ($toColumn !== null) {
            $this->setToColumn($toColumn);
        }
    }

    /**
     * Sets for the changed properties.
     *
     * @param array $properties
     *
     * @return void
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
     * @param \Propel\Generator\Model\Column $fromColumn
     *
     * @return void
     */
    public function setFromColumn(Column $fromColumn)
    {
        $this->fromColumn = $fromColumn;
    }

    /**
     * Returns the fromColumn property.
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getFromColumn()
    {
        return $this->fromColumn;
    }

    /**
     * Sets the toColumn property.
     *
     * @param \Propel\Generator\Model\Column $toColumn
     *
     * @return void
     */
    public function setToColumn(Column $toColumn)
    {
        $this->toColumn = $toColumn;
    }

    /**
     * Returns the toColumn property.
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getToColumn()
    {
        return $this->toColumn;
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return \Propel\Generator\Model\Diff\ColumnDiff
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
