<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

/**
 * Generates the empty PHP5 stub object class for user object model (OM).
 *
 * This class produces the empty stub class that can be customized with application
 * business logic, custom behavior, etc.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class ExtensionObjectBuilder extends AbstractObjectBuilder
{

    /**
     * Returns the name of the current class being built.
     * @return string
     */
    public function getUnprefixedClassName()
    {
        return $this->getTable()->getPhpName();
    }

    /**
     * Adds class phpdoc comment and opening of class.
     * @param string &$script The script will be modified in this method.
     */
    protected function addClassOpen(&$script)
    {
        $table = $this->getTable();
        $tableName = $table->getName();
        $tableDesc = $table->getDescription();
        $baseClassName = $this->getClassNameFromBuilder($this->getObjectBuilder());

        if ($this->getBuildProperty('generator.objectModel.addClassLevelComment')) {
            $script .= "
/**
 * Skeleton subclass for representing a row from the '$tableName' table.
 *
 * $tableDesc
 *";
            if ($this->getBuildProperty('generator.objectModel.addTimeStamp')) {
                $now = strftime('%c');
                $script .= '
 * This class was autogenerated by Propel ' . $this->getBuildProperty('general.version') . " on:
 *
 * $now
 *";
            }
            $script .= '
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */';
        }
        $script .= PHP_EOL . ($table->isAbstract() ? 'abstract ' : ''). 'class ' .$this->getUnqualifiedClassName()." extends $baseClassName
{
";
    }

    /**
     * Specifies the methods that are added as part of the stub object class.
     *
     * By default there are no methods for the empty stub classes; override this method
     * if you want to change that behavior.
     *
     * @see ObjectBuilder::addClassBody()
     *
     * @param string $script
     */
    protected function addClassBody(&$script)
    {
    }

    /**
     * Closes class.
     * @param string &$script The script will be modified in this method.
     */
    protected function addClassClose(&$script)
    {
        $script .= '
}
';
        $this->applyBehaviorModifier('extensionObjectFilter', $script, '');
    }
}
