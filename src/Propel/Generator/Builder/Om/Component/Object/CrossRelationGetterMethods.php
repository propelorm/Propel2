<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Model\CrossRelation;

/**
 * Adds all getter methods for crossRelations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CrossRelationGetterMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
//        if ($this->getEntity()->isActiveRecord()) {
//            foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
//                $this->addActiveCrossFKGet($crossRelation);
//            }
//        } else {
            foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
                $this->addCrossFKGet($crossRelation);
            }
//        }
    }

    /**
     * Adds the standard variant for cross relation getters.
     *
     * @param CrossRelation $crossRelation
     */
    protected function addCrossFKGet(CrossRelation $crossRelation)
    {
        if (1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys()) {
            $this->addCombinedCrossRelationGetter($crossRelation);
        } else {
            $this->addNormalCrossRelationGetter($crossRelation);
        }
    }

    /**
     * Adds the active record variant of cross relation getters.
     *
     * @param CrossRelation $crossRelation
     */
    protected function addActiveCrossFKGet(CrossRelation $crossRelation)
    {
        if (1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys()) {
            $this->addActiveCombinedCrossRelationGetter($crossRelation);
        } else {
            $this->addActiveNormalCrossRelationGetter($crossRelation);
        }
    }

    protected function addNormalCrossRelationGetter(CrossRelation $crossRelation)
    {
        $crossRefEntityName = $crossRelation->getMiddleEntity()->getName();

        foreach ($crossRelation->getRelations() as $relation) {
            $relatedName = $this->getRelationName($relation, true);
            $relatedObjectClassName = $this->getClassNameFromBuilder(
                $this->getBuilder()->getNewObjectBuilder($relation->getForeignEntity())
            );

            $collName = $this->getCrossRelationRelationVarName($relation);

            $body = <<<EOF
return \$this->$collName;
EOF;

            $description = <<<EOF
Gets a collection of $relatedObjectClassName objects related by a many-to-many relationship
to the current object by way of the $crossRefEntityName cross-reference entity.
EOF;

            $this->addMethod('get' . $relatedName)
                ->setType("ObjectCollection|{$relatedObjectClassName}[]")
                ->setTypeDescription("List of {$relatedObjectClassName} objects")
                ->setDescription($description)
                ->setBody($body);
        }
    }

    protected function addCombinedCrossRelationGetter(CrossRelation $crossRelation)
    {
        $crossRefEntityName = $crossRelation->getMiddleEntity()->getName();

        $relatedName = $this->getCrossRelationName($crossRelation, true);
        $collVarName = 'combination' . ucfirst($this->getCrossRelationVarName($crossRelation));

        $classNames = [];
        foreach ($crossRelation->getRelations() as $relation) {
            $classNames[] = $this->getClassNameFromBuilder(
                $this->getBuilder()->getNewObjectBuilder($relation->getForeignEntity())
            );
        }
        $classNames = implode(', ', $classNames);

        $body = "return \$this->$collVarName;";
        $description = <<<EOF
Gets a combined collection of $classNames objects related by a many-to-many relationship
to the current object by way of the $crossRefEntityName cross-reference entity.
EOF;

        $this->addMethod('get' . $relatedName)
            ->setType('ObjectCombinationCollection')
            ->setTypeDescription("Combination list of {$classNames} objects")
            ->setDescription($description)
            ->setBody($body);
    }

    protected function addActiveCombinedCrossRelationGetter(CrossRelation $crossRelation)
    {
        $refFK = $crossRelation->getIncomingRelation();
        $selfRelationName = $this->getRelationName($refFK, $plural = false);
        $crossRefEntityName = $crossRelation->getMiddleEntity()->getName();

        $relatedName = $this->getCrossRelationName($crossRelation, true);
        $collVarName = 'combination' . ucfirst($this->getCrossRelationVarName($crossRelation));

        $classNames = [];
        foreach ($crossRelation->getRelations() as $relation) {
            $classNames[] = $this->getClassNameFromBuilder(
                $this->getBuilder()->getNewObjectBuilder($relation->getForeignEntity())
            );
        }
        $classNames = implode(', ', $classNames);
        $relatedQueryClassName = $this->getClassNameFromBuilder(
            $this->getBuilder()->getNewStubQueryBuilder($crossRelation->getMiddleEntity())
        );

        $body = "
\$partial = \$this->{$collVarName}Partial && !\$this->isNew();
if (null === \$this->$collVarName || null !== \$criteria || \$partial) {
    if (\$this->isNew()) {
        // return empty collection
        if (null === \$this->$collVarName) {
            \$this->init{$relatedName}();
        }
    } else {

        \$query = $relatedQueryClassName::create(null, \$criteria)
            ->filterBy{$selfRelationName}(\$this)";
        foreach ($crossRelation->getRelations() as $fk) {
            $varName = $this->getRelationName($fk, $plural = false);
            $body .= "
            ->join{$varName}()";
        }

        $body .= "
                ;

        \$items = \$query->find(\$con);
        \$$collVarName = new ObjectCombinationCollection();
        foreach (\$items as \$item) {
            \$combination = [];
";

        foreach ($crossRelation->getRelations() as $fk) {
            $varName = $this->getRelationName($fk, $plural = false);
            $body .= "
            \$combination[] = \$item->get{$varName}();";
        }

        foreach ($crossRelation->getUnclassifiedPrimaryKeys() as $pk) {
            $varName = $pk->getName();
            $body .= "
            \$combination[] = \$item->get{$varName}();";
        }

        $body .= "
            \${$collVarName}[] = \$combination;
        }

        if (null !== \$criteria) {
            return \$$collVarName;
        }

        if (\$partial && \$this->{$collVarName}) {
            //make sure that already added objects gets added to the list of the database.
            foreach (\$this->{$collVarName} as \$obj) {
                if (!call_user_func_array([\${$collVarName}, 'contains'], \$obj)) {
                    \${$collVarName}[] = \$obj;
                }
            }
        }

        \$this->$collVarName = \$$collVarName;
        \$this->{$collVarName}Partial = false;
    }
}

return \$this->$collVarName;
";

        $objectClassName = $this->getObjectClassName();

        $description = <<<EOF
Gets a combined collection of $classNames objects related by a many-to-many relationship
to the current object by way of the $crossRefEntityName cross-reference entity.

If the \$criteria is not null, it is used to always fetch the results from the database.
Otherwise the results are fetched from the database the first time, then cached.
Next time the same method is called without \$criteria, the cached collection is returned.
If this $objectClassName is new, it will return
an empty collection or the current collection; the criteria is ignored on a new object.
EOF;

        $this->addMethod('get' . $relatedName)
            ->addSimpleParameter('criteria', 'Criteria', null)
            ->setType('ObjectCombinationCollection')
            ->setTypeDescription("Combination list of {$classNames} objects")
            ->setDescription($description)
            ->setBody($body);


        $relatedName = $this->getCrossRelationName($crossRelation, true);
        $firstFK = $crossRelation->getRelations()[0];
        $firstFkName = $this->getRelationName($firstFK, true);

        $relatedObjectClassName = $this->getClassNameFromBuilder(
            $this->getBuilder()->getNewObjectBuilder($firstFK->getForeignEntity())
        );
        $signature = $shortSignature = $normalizedShortSignature = $phpDoc = [];
        $this->extractCrossInformation(
            $crossRelation,
            [$firstFK],
            $signature,
            $shortSignature,
            $normalizedShortSignature,
            $phpDoc
        );

        $signature = array_map(
            function (PhpParameter $item) {
                $item->setDefaultValue(null);
            },
            $signature
        );
        $phpDoc = implode(', ', $phpDoc);
        $shortSignature = implode(', ', $shortSignature);

        $body = "return \$this->create{$firstFkName}Query($shortSignature, \$criteria)->find();";

        $description = <<<EOF
Returns a not cached ObjectCollection of $relatedObjectClassName objects. This will hit always the databases.
If you have attached new $relatedObjectClassName object to this object you need to call `save` first to get
the correct return value. Use get$relatedName() to get the current internal state.
$phpDoc
EOF;

        $method = $this->addMethod('get' . $firstFkName)
            ->addSimpleParameter('criteria', 'Criteria', null)
            ->setType("{$relatedObjectClassName}[]|ObjectCollection")
            ->setDescription($description)
            ->setBody($body);

        foreach ($signature as $parameter) {
            $method->addParameter($parameter);
        }
    }

    protected function addActiveNormalCrossRelationGetter(CrossRelation $crossRelation)
    {
        $refFK = $crossRelation->getIncomingRelation();
        $selfRelationName = $this->getRelationName($refFK, $plural = false);
        $crossRefEntityName = $crossRelation->getMiddleEntity()->getName();

        foreach ($crossRelation->getRelations() as $relation) {
            $relatedName = $this->getRelationName($relation, true);
            $relatedObjectClassName = $this->getClassNameFromBuilder(
                $this->getBuilder()->getNewObjectBuilder($relation->getForeignEntity())
            );
            $relatedQueryClassName = $this->getClassNameFromBuilder(
                $this->getBuilder()->getNewStubQueryBuilder($relation->getForeignEntity())
            );

            $collName = $this->getCrossRelationRelationVarName($relation);

            $body = <<<EOF
\$partial = \$this->{$collName}Partial && !\$this->isNew();
if (null === \$this->$collName || null !== \$criteria || \$partial) {
    if (\$this->isNew()) {
        // return empty collection
        if (null === \$this->$collName) {
            \$this->init{$relatedName}();
        }
    } else {

        \$query = $relatedQueryClassName::create(null, \$criteria)
            ->filterBy{$selfRelationName}(\$this);
        \$$collName = \$query->find(\$con);
        if (null !== \$criteria) {
            return \$$collName;
        }

        if (\$partial && \$this->{$collName}) {
            //make sure that already added objects gets added to the list of the database.
            foreach (\$this->{$collName} as \$obj) {
                if (!\${$collName}->contains(\$obj)) {
                    \${$collName}[] = \$obj;
                }
            }
        }

        \$this->$collName = \$$collName;
        \$this->{$collName}Partial = false;
    }
}

return \$this->$collName;
EOF;

            $objectClassName = $this->getObjectClassName();
            $description = <<<EOF
Gets a collection of $relatedObjectClassName objects related by a many-to-many relationship
to the current object by way of the $crossRefEntityName cross-reference table.

If the \$criteria is not null, it is used to always fetch the results from the database.
Otherwise the results are fetched from the database the first time, then cached.
Next time the same method is called without \$criteria, the cached collection is returned.
If this $objectClassName is new, it will return
an empty collection or the current collection; the criteria is ignored on a new object.
EOF;


            $this->addMethod('get' . $relatedName)
                ->addSimpleParameter('criteria', 'Criteria', null)
                ->setType("ObjectCollection|{$relatedObjectClassName}[]")
                ->setTypeDescription("List of {$relatedObjectClassName} objects")
                ->setDescription($description)
                ->setBody($body);
        }
    }
}