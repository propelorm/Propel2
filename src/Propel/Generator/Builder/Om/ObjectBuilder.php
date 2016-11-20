<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Common\Types\BuildableFieldTypeInterface;
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

        if ($this->getEntity()->isActiveRecord()) {
            $this->getDefinition()->addTrait($this->getActiveRecordTraitName());
        }

        $this->applyComponent('Object\\Properties');
        $this->applyComponent('Object\\MagicToStringMethod');
        $this->applyComponent('Object\\RelationProperties');
        $this->applyComponent('Object\\ReferrerRelationProperties');
        $this->applyComponent('Object\\CrossRelationProperties');

        $this->applyComponent('Object\\PropertyGetterMethods');
        $this->applyComponent('Object\\RelationGetterMethods');
        $this->applyComponent('Object\\CrossRelationGetterMethods');
        $this->applyComponent('Object\\CrossRelationSetterMethods');

        if ($this->getEntity()->isActiveRecord()) {
            $this->applyComponent('Object\\CrossRelationCountMethods');
            $this->applyComponent('Object\\ReferrerRelationCountMethods');
        }

        $this->applyComponent('Object\\PropertySetterMethods');
        $this->applyComponent('Object\\RelationSetterMethods');
        $this->applyComponent('Object\\ReferrerRelationAddMethods');
        $this->applyComponent('Object\\ReferrerRelationGetMethods');
        $this->applyComponent('Object\\ReferrerRelationSetMethods');
        $this->applyComponent('Object\\CrossRelationAdderMethods');
        $this->applyComponent('Object\\CrossRelationRemoverMethods');

        $this->applyComponent('Object\\ConstructorMethod');
    }
}