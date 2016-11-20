<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use gossi\codegen\model\PhpTrait;

/**
 * Generates the empty PHP5 stub object class for user object model (OM).
 *
 * This class produces the empty stub class that can be customized with application
 * business logic, custom behavior, etc.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ActiveRecordTraitBuilder extends AbstractBuilder
{
    /**
     * @return string
     */
    public function getFullClassName($fullClassName = 'Base', $classPrefix = '')
    {
        return parent::getFullClassName('Base') . 'ActiveRecordTrait';
    }

    public function buildClass()
    {
        $this->definition = new PhpTrait($this->getFullClassName());
        $this->getDefinition()->addTrait('\Propel\Runtime\ActiveRecordTrait');

        if ($this->isAddGenericMutators()) {
            $this->applyComponent('ActiveRecordTrait\\BooleanAccessorMethods');
            $this->applyComponent('ActiveRecordTrait\\GenericMutatorMethods');
        }

        if ($this->isAddGenericAccessors()) {
            $this->applyComponent('ActiveRecordTrait\\GenericAccessorMethods');
        }
    }
}
