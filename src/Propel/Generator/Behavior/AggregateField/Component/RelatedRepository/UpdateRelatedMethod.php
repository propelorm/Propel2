<?php

namespace Propel\Generator\Behavior\AggregateField\Component\RelatedRepository;

use gossi\codegen\model\PhpProperty;
use Propel\Generator\Behavior\AggregateField\AggregateFieldBehavior;
use Propel\Generator\Behavior\AggregateField\AggregateFieldRelationBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class UpdateRelatedMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        /** @var AggregateFieldRelationBehavior $behavior */
        $behavior = $this->getBehavior();

        $relation = $behavior->getRelation();
        $relationName = $behavior->getRelation()->getName();
        $updateMethodName = $behavior->getParameter('update_method');
        $aggregateName = ucfirst($behavior->getParameter('aggregate_name'));
        $repositoryClass = $this->getRepositoryClassNameForEntity($relation->getForeignEntity(), true);
        $objectGetter = 'get' . $this->getRelationName($relation);

        $body = "
if (!\$entities) {
    return;
}

\$pks = [];
\$session = \$this->getConfiguration()->getSession();
/** @var \\{$relation->getEntity()->getFullClassName()}[] \$entities */
foreach (\$entities as \$entity) {
    \$pk = [];

    if (\$session->hasKnownValues(\$entity) && \$lastValues = \$session->getLastKnownValues(\$entity)) {";

        foreach ($relation->getLocalFieldObjects() as $field) {
            $body .= "
        \$pk[] = \$lastValues['{$field->getName()}'];";
        }

        if ($relation->isComposite()) {
            $body .= "
        \$pks[] = \$pk;";
        } else {
            $body .= "
        \$pks[] = \$pk[0];";
        }


        $body .= "
    }


    \$pk = [];
    if (\$object = \$entity->{$objectGetter}()) {";

        foreach ($relation->getForeignFieldObjects() as $field) {
            $fieldGetter = 'get' . ucfirst($field->getName());
            $body .= "
        \$pk[] = \$object->{$fieldGetter}();";
        }

        if ($relation->isComposite()) {
            $body .= "
        \$pks[] = \$pk;";
        } else {
            $body .= "
        \$pks[] = \$pk[0];";
        }

        $body .= "
    }
}

/** @var \\$repositoryClass \$relatedRepo */
\$relatedRepo = \$this->getConfiguration()->getRepository('{$relation->getForeignEntity()->getFullClassName()}');
\$relatedQuery = \$relatedRepo->createQuery();
\$relatedObjects = \$relatedQuery
    ->filterByPrimaryKeys(array_unique(\$pks))
    ->find();
foreach (\$relatedObjects as \$relatedObject) {
    \$relatedRepo->$updateMethodName(\$relatedObject);
}
";
        $name = 'updateRelated' . $relationName . $aggregateName;

        $this->addMethod($name)
            ->setDescription("[AggregateField-related] Update the aggregate field in the related $relationName object.")
            ->addSimpleParameter('entities')
            ->setBody($body);
    }
}
