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
class Setters extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        if ('LeftValue' !== $this->getBehavior()->getFieldForParameter('left_field')) {
            $this->addSetLeft();
        }
        if ('RightValue' !== $this->getBehavior()->getFieldForParameter('right_field')) {
            $this->addSetRight();
        }
        if ('Level' !== $this->getBehavior()->getFieldForParameter('level_field')) {
            $this->addSetLevel();
        }
        if ('true' === $this->getBehavior()->getParameter('use_scope')
            && 'ScopeValue' !== $this->getBehavior()->getFieldForParameter('scope_field')) {
            $this->addSetScope();
        }
    }

    protected function addSetLeft()
    {
        $this->addMethod('setLeftValue')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object (for fluent API support)')
            ->setDescription("Proxy setter method for the left value of the nested set model.
It provides a generic way to set the value, whatever the actual column name is.")
            ->addSimpleDescParameter('v', 'int', 'The nested set left value')
            ->setBody("return \$this->set{$this->getBehavior()->getFieldForParameter('left_field')->getCamelCaseName()}(\$v);")
        ;
    }

    protected function addSetRight()
    {
        $this->addMethod('setRightValue')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object (for fluent API support)')
            ->setDescription("Proxy setter method for the right value of the nested set model.
It provides a generic way to set the value, whatever the actual column name is.")
            ->addSimpleDescParameter('v', 'int', 'The nested set right value')
            ->setBody("return \$this->set{$this->getBehavior()->getFieldForParameter('right_field')->getCamelCaseName()}(\$v);")
        ;
    }

    protected function addSetLevel()
    {
        $this->addMethod('setLevel')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object (for fluent API support)')
            ->setDescription("Proxy setter method for the level value of the nested set model.
It provides a generic way to set the value, whatever the actual column name is.")
            ->addSimpleDescParameter('v', 'int', 'The nested set level value')
            ->setBody("return \$this->set{$this->getBehavior()->getFieldForParameter('level_field')->getCamelCaseName()}(\$v);")
        ;
    }

    protected function addSetScope()
    {
        $this->addMethod('setScopeValue')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object (for fluent API support)')
            ->setDescription("Proxy setter method for the scope value of the nested set model.
It provides a generic way to set the value, whatever the actual column name is.")
            ->addSimpleDescParameter('v', 'int', 'The nested set scope value')
            ->setBody("return \$this->set{$this->getBehavior()->getFieldForParameter('scope_field')->getCamelCaseName()}(\$v);")
        ;
    }
}
