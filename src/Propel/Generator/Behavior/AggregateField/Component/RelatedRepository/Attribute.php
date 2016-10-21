<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AggregateField\Component\RelatedRepository;

use Propel\Generator\Behavior\AggregateField\AggregateFieldRelationBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Attribute extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var AggregateFieldRelationBehavior $behavior */
        $behavior = $this->getBehavior();

        $relationName = $behavior->getRelation()->getName();
        $variableName = $relationName . ucfirst($behavior->getParameter('aggregate_name'));
        $relatedClass = $behavior->getForeignEntity()->getFullClassName();
        $this->addProperty("afCache{$variableName}")
            ->setType($relatedClass . '[]')
            ->setDescription('[AggregateField-related]')
            ->setVisibility('protected')
        ;
    }
}
