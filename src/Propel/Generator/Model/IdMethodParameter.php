<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
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
    private $name;
    private $value;

    /** @var Entity */
    private $parentEntity;

    protected function setupObject()
    {
        $this->name = $this->getAttribute('name');
        $this->value = $this->getAttribute('value');
    }

    /**
     * Returns the parameter name.
     *
     * @param string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the parameter name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the parameter value.
     *
     * @param mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the parameter value.
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Sets the parent table.
     *
     * @param Entity $parent
     */
    public function setEntity(Entity $parent)
    {
        $this->parentEntity = $parent;
    }

    /**
     * Returns the parent table.
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->parentEntity;
    }

    /**
     * Returns the parent table name.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->parentEntity->getName();
    }
}
