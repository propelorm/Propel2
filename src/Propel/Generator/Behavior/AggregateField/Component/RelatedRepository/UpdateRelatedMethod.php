<?php

namespace Propel\Generator\Behavior\AggregateField\Component\RelatedRepository;

use gossi\codegen\model\PhpProperty;
use Propel\Generator\Behavior\AggregateField\AggregateFieldBehavior;
use Propel\Generator\Behavior\AggregateField\AggregateFieldRelationBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class UpdateRelatedMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var AggregateFieldRelationBehavior $behavior */
        $behavior = $this->getBehavior();

        $relationName = $behavior->getRelationName();
//        $variableName = lcfirst($relationName);
        $variableName = lcfirst($relationName . $behavior->getParameter('aggregate_name'));
        $updateMethodName = $behavior->getParameter('update_method');
        $aggregateName = $behavior->getParameter('aggregate_name');

        $body = "
foreach (\$this->afCache{$variableName}s as \${$variableName}) {
    \${$variableName}->{$updateMethodName}();
}
\$this->{$variableName}s = array();
";
        $name = 'updateRelated' . $relationName . $aggregateName . 's';

        $this->addMethod($name)
            ->setDescription("[AggregateField-related] Update the aggregate column in the related $relationName object.")
            ->addSimpleParameter('entity', 'object')
            ->setBody($body);
    }
}