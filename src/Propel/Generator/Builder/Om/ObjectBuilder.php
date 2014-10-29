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

/**
 * Generates a POPO.
 *
 * This class produces the actual entity object class (e.g. MyEntity) which contains
 * all the accessor and setter methods as well as fields as class properties.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ObjectBuilder extends AbstractBuilder
{

    use NamingTrait;

    public function buildClass()
    {
        //todo, make it depending on <entity activeRecord="true">
        $this->getDefinition()->declareUse($this->getActiveRecordTraitName(true));
        $this->getDefinition()->addTrait($this->getActiveRecordTraitName());

        $this->applyComponent('Object\\Properties');
        $this->applyComponent('Object\\RelationProperties');
        $this->applyComponent('Object\\ReferrerRelationProperties');
        $this->applyComponent('Object\\CrossRelationProperties');

        if ($this->isAddGenericAccessors()) {
            $this->applyComponent('Object\\PropertyGetterMethods');
            $this->applyComponent('Object\\RelationGetterMethods');
        }

        if ($this->isAddGenericMutators()) {
            $this->applyComponent('Object\\PropertySetterMethods');
            $this->applyComponent('Object\\RelationSetterMethods');
            $this->applyComponent('Object\\ReferrerRelationAddMethods');
        }
    }
}