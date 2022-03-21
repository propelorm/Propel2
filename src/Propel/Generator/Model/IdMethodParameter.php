<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

/**
 * Information related to an ID method strategy.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class IdMethodParameter extends MappingModel
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var \Propel\Generator\Model\Table
     */
    private $parentTable;

    /**
     * @return void
     */
    protected function setupObject(): void
    {
        $this->name = $this->getAttribute('name');
        $this->value = $this->getAttribute('value');
    }

    /**
     * Returns the parameter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the parameter name.
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
     * Returns the parameter value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the parameter value.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * Sets the parent table.
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
     * Returns the parent table.
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getTable(): Table
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
}
