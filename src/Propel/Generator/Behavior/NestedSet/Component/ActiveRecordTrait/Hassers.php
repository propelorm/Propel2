<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Hassers extends NestedSetBuildComponent
{
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
            ->setBody("return \$this->getLevel() > 0;");
    }

    protected function addHasPrevSibling()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->hasPrevSibling(\$this, \$con);
";
        $this->addMethod('hasPrevSibling')
            ->setType('bool')
            ->setDescription('Determines if the node has previous sibling.')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body)
        ;
    }

    protected function addHasNextSibling()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->hasNextSibling(\$this, \$con);
";
        $this->addMethod('hasNextSibling')
            ->setDescription('Determines if the node has next sibling.')
            ->setType('bool')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addHasChildren()
    {
        $this->addMethod('hasChildren')
            ->setDescription('Tests if node has children.')
            ->setType('bool')
            ->setBody("return (\$this->getRightValue() - \$this->getLeftValue()) > 1;")
        ;
    }
}
