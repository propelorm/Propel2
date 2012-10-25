<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\CustomObjectCollection;

use Propel\Generator\Builder\Om\AbstractOMBuilder;

/**
 * Adds an object collection class unique to the class
 *
 * @author Lee Leathers
 */
class AddObjectCollectionBuilder extends AbstractOMBuilder
{
    public $overwrite = false;

    public function getUnprefixedClassname()
    {
        return $this->getStubObjectBuilder()->getUnprefixedClassname() . 'ObjectCollection';
    }

    public function getNamespace()
    {
        if (!$this->getStubObjectBuilder()->getNamespace()) {
            return "ObjectCollection";
        }

        return $this->getStubObjectBuilder()->getNamespace() . '\ObjectCollection';
    }

    public function getPackage()
    {
        return parent::getPackage() . '.ObjectCollection';
    }

    protected function addClassOpen(&$script)
    {
        $table = $this->getTable();
        $tableName = $table->getName();
        $script .= "
/**
 * Collection class that will be returned from quering for the '$tableName' table.
 *
 */
class " . $this->getUnprefixedClassname() . " extends \Propel\Runtime\Collection\ObjectCollection
{
";
    }

    protected function addClassBody(&$script)
    {
    }

    protected function addClassClose(&$script)
    {
        $script .= "
}";
    }
}
