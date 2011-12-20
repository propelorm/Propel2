<?php

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Generator\Builder\Om\AbstractOMBuilder;

class AddClassBehaviorBuilder extends AbstractOMBuilder
{
    public $overwrite = true;

    public function getPackage()
    {
        return parent::getPackage();
    }

    /**
     * Returns the name of the current class being built.
     * @return     string
     */
    public function getUnprefixedClassname()
    {
        return $this->getStubObjectBuilder()->getUnprefixedClassname() . 'FooClass';
    }

    /**
     * Adds class phpdoc comment and openning of class.
     * @param      string &$script The script will be modified in this method.
     */
    protected function addClassOpen(&$script)
    {
        $table = $this->getTable();
        $tableName = $table->getName();
        $script .= "
/**
 * Test class for Additional builder enabled on the '$tableName' table.
 *
 */
class ".$this->getUnqualifiedClassname()."
{
";
    }

    /**
     * Specifies the methods that are added as part of the basic OM class.
     * This can be overridden by subclasses that wish to add more methods.
     * @see        ObjectBuilder::addClassBody()
     */
    protected function addClassBody(&$script)
    {
        $script .= "  // no code";
    }

    /**
     * Closes class.
     * @param      string &$script The script will be modified in this method.
     */
    protected function addClassClose(&$script)
    {
        $script .= "
} // " . $this->getUnqualifiedClassname() . "
";
    }
}
