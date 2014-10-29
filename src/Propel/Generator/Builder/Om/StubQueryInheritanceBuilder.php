<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Exception\BuildException;

/**
 * Generates the empty PHP5 stub query class for use with single table inheritance.
 *
 * This class produces the empty stub class that can be customized with
 * application business logic, custom behavior, etc.
 *
 * @author François Zaninotto
 */
class StubQueryInheritanceBuilder extends AbstractBuilder
{
    /**
     * The current child "object" we are operating on.
     *
     */
    protected $child;

    use NamingTrait;

    /**
     * @return string
     */
    public function getFullClassName($injectNamespace = '', $classPrefix = '')
    {
        return $this->getChild()->getClassName() . 'Query';
    }

    public function buildClass()
    {
        if (!$this->getEntity()->getRepository()) {
            return false;
        }

        $baseBuilder = $this->getNewQueryInheritanceBuilder($this->getChild());
        $parentClass = $this->getClassNameFromBuilder($baseBuilder, true);

        $this->getDefinition()->setParentClassName($parentClass);
    }

    /**
     * Set the child object that we're operating on currently.
     *
     * @param Inheritance $child Inheritance
     */
    public function setChild(Inheritance $child)
    {
        $this->child = $child;
    }

    /**
     * Returns the child object we're operating on currently.
     *
     * @return Inheritance
     * @throws BuildException
     */
    public function getChild()
    {
        if (!$this->child) {
            throw new BuildException("The MultiExtendObjectBuilder needs to be told which child class to build (via setChild() method) before it can build the stub class.");
        }

        return $this->child;
    }
}
