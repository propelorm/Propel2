<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Hassers extends BuildComponent
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
return \$this->getRepository()->getNestedManager()->hasPrevSibling(\$this, \$con);
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
return \$this->getRepository()->getNestedManager()->hasNextSibling(\$this, \$con);
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
