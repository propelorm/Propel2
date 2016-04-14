<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Getters extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        if ('LeftValue' !== $this->getBehavior()->getFieldForParameter('left_field')) {
            $this->addGetLeft();
        }
        if ('RightValue' !== $this->getBehavior()->getFieldForParameter('right_field')) {
            $this->addGetRight();
        }
        if ('Level' !== $this->getBehavior()->getFieldForParameter('level_field')) {
            $this->addGetLevel();
        }
        if ('true' === $this->getBehavior()->getParameter('use_scope')
            && 'ScopeValue' !== $this->getBehavior()->getFieldForParameter('scope_field')) {
            $this->addGetScope();
        }
    }

    protected function addGetLeft()
    {
        $this->addMethod('getLeftValue')
            ->setType('int', 'The nested set left value')
            ->setDescription("Proxy getter method for the left value of the nested set model.
It provides a generic way to get the value, whatever the actual column name is.")
            ->setBody("return \$this->{$this->getBehavior()->getFieldAttribute('left_field')};")
        ;
    }

    protected function addGetRight()
    {
        $this->addMethod('getRightValue')
            ->setType('int', 'The nested set right value')
            ->setDescription("Proxy getter method for the right value of the nested set model.
It provides a generic way to get the value, whatever the actual column name is.")
            ->setBody("return \$this->{$this->getBehavior()->getFieldAttribute('right_field')};")
        ;
    }

    protected function addGetLevel()
    {
        $this->addMethod('getLevel')
            ->setType('int', 'The nested set level value')
            ->setDescription("Proxy getter method for the level value of the nested set model.
It provides a generic way to get the value, whatever the actual column name is.")
            ->setBody("return \$this->{$this->getBehavior()->getFieldAttribute('level_field')};")
        ;
    }

    protected function addGetScope()
    {
        $this->addMethod('getScopeValue')
            ->setType('int', 'The nested set scope value')
            ->setDescription("Proxy getter method for the scope value of the nested set model.
It provides a generic way to get the value, whatever the actual column name is.")
            ->setBody("return \$this->{$this->getBehavior()->getFieldAttribute('scope_field')};")
        ;
    }
}
