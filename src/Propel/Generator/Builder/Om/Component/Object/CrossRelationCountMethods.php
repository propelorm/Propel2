<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Model\CrossRelation;

/**
 * Adds all count methods for crossRelations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CrossRelationCountMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        // many-to-many relationships
        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addCrossRelationCount($crossRelation);
        }
    }

    /**
     * @param CrossRelation $crossRelation
     */
    protected function addCrossRelationCount(CrossRelation $crossRelation)
    {
        $refRelation = $crossRelation->getIncomingRelation();
        $selfRelationName = $this->getRelationName($refRelation, $plural = false);

        $multi = 1 < count($crossRelation->getRelations()) || !!$crossRelation->getUnclassifiedPrimaryKeys();

        $relatedName = $this->getCrossRelationName($crossRelation, true);
        $crossRefEntityName = $crossRelation->getMiddleEntity()->getName();

        if ($multi) {
            list($relatedObjectClassName) = $this->getCrossRelationInformation($crossRelation);
            $collName = 'combination' . ucfirst($this->getCrossRelationVarName($crossRelation));
            $relatedQueryClassName = $this->getClassNameFromBuilder(
                $this->getBuilder()->getNewStubQueryBuilder($crossRelation->getMiddleEntity())
            );
        } else {
            $crossFK = $crossRelation->getRelations()[0];
            $relatedObjectClassName = $crossFK->getForeignEntity()->getName();
            $collName = $this->getCrossRelationRelationVarName($crossFK);
            $relatedQueryClassName = $this->getClassNameFromBuilder(
                $this->getBuilder()->getNewStubQueryBuilder($crossFK->getForeignEntity())
            );
        }

        $description = <<<EOF
Gets the number of $relatedObjectClassName objects related by a many-to-many relationship
to the current object by way of the $crossRefEntityName cross-reference entity.
EOF;

        $body = <<<EOF
\$partial = \$this->{$collName}Partial && !\$this->isNew();
if (null === \$this->$collName || null !== \$criteria || \$partial) {
    if (\$this->isNew() && null === \$this->$collName) {
        return 0;
    } else {

        if (\$partial && !\$criteria) {
            return count(\$this->get$relatedName());
        }

        \$query = $relatedQueryClassName::create(null, \$criteria);
        if (\$distinct) {
            \$query->distinct();
        }

        return \$query
            ->filterBy{$selfRelationName}(\$this)
            ->count();
    }
} else {
    return count(\$this->$collName);
}
EOF;


        $this->addMethod('count' . $relatedName)
            ->setDescription($description)
            ->addSimpleParameter('criteria', 'Criteria', null)
            ->addSimpleParameter('distinct', 'boolean', false)
            ->setBody($body)
            ->setType('integer')
            ->setTypeDescription("the number of related $relatedObjectClassName objects");

        if ($multi) {
            $relatedName = $this->getCrossRelationName($crossRelation, true);
            $firstRelation = $crossRelation->getRelations()[0];
            $firstRelationName = $this->getRelationName($firstRelation, true);

            $relatedObjectClassName = $firstRelation->getForeignEntity()->getName();
            $signature = $shortSignature = $normalizedShortSignature = $phpDoc = [];
            $this->extractCrossInformation(
                $crossRelation,
                [$firstRelation],
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

            $description = <<<EOF
Returns the not cached count of $relatedObjectClassName objects. This will hit always the databases.
If you have attached new $relatedObjectClassName object to this object you need to call `save` first to get
the correct return value. Use get$relatedName() to get the current internal state.
$phpDoc
EOF;

            $body = "return \$this->create{$firstRelationName}Query($shortSignature, \$criteria)->count();";

            $method = $this->addMethod('count' . $firstRelationName)
                ->setDescription($description)
                ->setBody($body)
                ->setType('integer');

            foreach ($signature as $parameter) {
                $method->addParameter($parameter);
            }
        }
    }
}