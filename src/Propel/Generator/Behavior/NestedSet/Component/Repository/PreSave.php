<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Runtime\Configuration;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PreSave extends BuildComponent
{
    use NamingTrait;

    /**
     * @return string
     */
    public function process()
    {
        $code = "
foreach (\$event->getEntities() as \$entity) {
    if (!\$entity instanceof {$this->getObjectClassName()}) {
        continue;
    }

    if (\$this->getConfiguration()->getSession()->isNew(\$entity) && \$this->getNestedManager()->isRoot(\$entity)) {
        // check if no other root exist in, the tree

        \$nbRoots = \$this->createQuery()
            ->filterBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}(1)
            ";

        if ($this->getBehavior()->useScope()) {
            $code .= "->inTree(\$entity->getScopeValue())
            ";
        }

        $code .= "->count();
        if (\$nbRoots > 0) {
            throw new PropelException(";

        if ($this->getBehavior()->useScope()) {
            $code .= "sprintf('A root node already exists in this tree with scope \"%s\".', \$entity->getScopeValue())";
        } else {
            $code .= "'A root node already exists in this tree. To allow multiple root nodes, add the `use_scope` parameter in the nested_set behavior tag.'";
        }

        $code .= ");
        }
    }
}

\$this->processNestedSetQueries();";
        
        return $code;
    }
}
