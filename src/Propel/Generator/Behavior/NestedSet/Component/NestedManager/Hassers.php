<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\NestedManager;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Hassers extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addHasParent();
        $this->addHasPrevSibling();
        $this->addHasNextSibling();
        $this->addHasChildren();
    }

    protected function addHasParent()
    {
        $this->addMethod('hasParent')
            ->setDescription('Tests if object has an ancestor.')
            ->setType('bool')
            ->addSimpleParameter('object', $this->getObjectClassName())
            ->setBody("return \$object->getLevel() > 0;");
    }

    protected function addHasPrevSibling()
    {
        $body = "
if (!\$this->isValid(\$node)) {
    return false;
}

{$this->getRepositoryAssignment()}

return \$repository->createQuery()
    ->filterBy{$this->getBehavior()->getFieldForParameter('right_field')->getMethodName()}(\$node->getLeftValue() - 1)";

        if ($this->getBehavior()->useScope()) {
            $body .= "
    ->inTree(\$node->getScopeValue())";
        }

    $body .= "
    ->count(\$con) > 0;
";
        $this->addMethod('hasPrevSibling')
            ->setType('bool')
            ->setDescription('Determines if the node has previous sibling.')
            ->addSimpleDescParameter('node', "{$this->getObjectClassName()}", 'The node to find the previous sibling of.')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body)
        ;
    }

    protected function addHasNextSibling()
    {
        $body = "
if (!\$this->isValid(\$node)) {
    return false;
}

{$this->getRepositoryAssignment()}

return \$repository->createQuery()
    ->filterBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}(\$node->getRightValue() + 1)";

        if ($this->getBehavior()->useScope()) {
            $body .= "
    ->inTree(\$object->getScopeValue())";
        }

        $body .= "
    ->count(\$con) > 0;
";
        $this->addMethod('hasNextSibling')
            ->setDescription('Determines if the node has next sibling.')
            ->setType('bool')
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addHasChildren()
    {
        $this->addMethod('hasChildren')
            ->setDescription('Tests if node has children.')
            ->setType('bool')
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->setBody("return (\$node->getRightValue() - \$node->getLeftValue()) > 1;")
        ;
    }
}
