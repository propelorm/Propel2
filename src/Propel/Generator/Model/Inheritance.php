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
 * A class for information regarding possible objects representing a table.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Inheritance extends MappingModel
{
    private $key;
    private $className;
    private $package;
    private $ancestor;
    private $column;

    /**
     * Returns a key name.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets a key name.
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the parent column.
     *
     * @return Column
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Sets the parent column
     *
     * @param Column $column
     */
    public function setColumn(Column $column)
    {
        $this->column = $column;
    }

    /**
     * Returns the class name.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Sets the class name.
     *
     * @param string $name
     */
    public function setClassName($name)
    {
        $this->className = $name;
    }

    /**
     * Returns the package.
     *
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Sets the package.
     *
     * @param string $package
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }

    /**
     * Returns the ancestor value.
     *
     * @return string
     */
    public function getAncestor()
    {
        return $this->ancestor;
    }

    /**
     * Sets the ancestor.
     *
     * @param string $ancestor
     */
    public function setAncestor($ancestor)
    {
        $this->ancestor = $ancestor;
    }

    protected function setupObject()
    {
        $this->key       = $this->getAttribute('key');
        $this->className = $this->getAttribute('class');
        $this->package   = $this->getAttribute('package');
        $this->ancestor  = $this->getAttribute('extends');
    }
}
