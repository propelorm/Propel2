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
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Inheritance;

/**
 * Generates the empty PHP5 stub query class for use with single table
 * inheritance.
 *
 * This class produces the empty stub class that can be customized with
 * application business logic, custom behavior, etc.
 *
 *
 * @author François Zaninotto
 */
class QueryInheritanceBuilder extends AbstractBuilder
{
    use NamingTrait;

    /**
     * The current child "object" we are operating on.
     */
    protected $child;

    /**
     * @return string
     */
    public function getFullClassName($injectNamespace = '', $classPrefix = '')
    {
        $fullClassName = $this->getChild()->getClassName();
        $namespace = explode('\\', $fullClassName);
        $className = array_pop($namespace);

        return implode('\\', $namespace) . '\\Base\\Base' . $className . 'Query';
    }

    public function buildClass()
    {
        $parentClass = $this->getParentClassName();
        $this->getDefinition()->setParentClassName($parentClass);
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));

        $this->applyComponent('QueryInheritance\\FilterHooks');
    }

    /**
     * Returns classpath to parent class.
     *
     * @return string
     */
    protected function getParentClassName()
    {
        if (is_null($this->getChild()->getAncestor())) {
            return $this->getNewStubQueryBuilder($this->getEntity())->getFullClassName();
        }

        $ancestorClassName = $this->getChild()->getAncestor();

        if ($this->getDatabase()->hasEntity($ancestorClassName)) {
            return $this
                ->getNewStubQueryBuilder($this->getDatabase()->getEntity($ancestorClassName))->getFullClassName();
        }

        // find the inheritance for the parent class
        foreach ($this->getEntity()->getChildrenField()->getChildren() as $child) {
            if ($child->getClassName() == $ancestorClassName) {
                return $this->getNewStubQueryInheritanceBuilder($child)->getFullClassName();
            }
        }
    }

    /**
     * Sets the child object that we're operating on currently.
     *
     * @param Inheritance $child
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
            throw new BuildException(
                "The PHP5MultiExtendObjectBuilder needs to be told which child class to build (via setChild() method) before it can build the stub class."
            );
        }

        return $this->child;
    }
}
