<?php

namespace Propel\Generator\Behavior\AggregateField\Component\Repository;

use Propel\Generator\Behavior\AggregateField\AggregateFieldBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class UpdateMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var AggregateFieldBehavior $behavior */
        $behavior = $this->getBehavior();

        $conditions = array();
        if ($behavior->getParameter('condition')) {
            $conditions[] = $behavior->getParameter('condition');
        }

        $name = ucfirst($behavior->getField()->getName());
        $setter = 'set' . $name;

        $body = "
\$entity->{$setter}(\$this->compute{$name}(\$entity));
\$this->persist(\$entity);
";

        $this->addMethod('update' . ucfirst($behavior->getField()->getName()))
            ->addSimpleDescParameter('entity', 'object', 'The entity object')
            ->addSimpleDescParameter('save', 'boolean', 'Save the entity immediately', false)
            ->setDescription("[AggregateField] Updates the aggregate field {$behavior->getField()->getName()}.")
            ->setBody($body)
        ;
    }
}