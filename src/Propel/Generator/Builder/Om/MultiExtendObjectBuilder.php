<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Inheritance;

/**
 * Generates the empty PHP5 stub object class for use with inheritance in the
 * user object model (OM).
 *
 * This class produces the empty stub class that can be customized with
 * application business logic, custom behavior, etc.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class MultiExtendObjectBuilder extends AbstractBuilder
{
    /**
     * The current child "object" we are operating on.
     *
     * @var Inheritance $child
     */
    private $child;

    /**
     * Sets the child object that we're operating on currently.
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
            throw new BuildException(
                "The MultiExtendBuilder needs to be told which child class to build (via setChild() method) before it can build the stub class."
            );
        }

        return $this->child;
    }

    public function buildClass()
    {
        $this->getDefinition()->setParentClassName($this->getParentClassName());

        $this->applyComponent('MultiExtendObject\\Constructor');
    }
}
