<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all referrer relations as class properties.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ReferrerRelationAddMethods extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        $entity = $this->getEntity();

        foreach ($entity->getReferrers() as $refRelation) {
            if ($refRelation->isLocalPrimaryKey()) {
                //one-to-one
                continue;
            }

            $this->addRefAddMethod($refRelation);
        }
    }

    /**
     * Adds the attributes used to store objects that have referrer fkey relationships to this object.
     *
     * @param Relation $refRelation
     */
    protected function addRefAddMethod(Relation $refRelation)
    {
        $varName = lcfirst($this->getRefRelationPhpName($refRelation));
        $className = $this->getObjectClassName();
        $methodName = 'add' . ucfirst($varName);
        $colVarName = $this->getRefRelationCollVarName($refRelation);
        $relationClassName = $this->getClassNameFromEntity($refRelation->getEntity());

        $body = "\$this->{$colVarName}[] = \$$varName;";

        $this->addMethod($methodName)
            ->addSimpleParameter($varName, $relationClassName)
            ->setType($className . '|$this')
            ->setDescription("Associate a $relationClassName to this object")
            ->setBody($body);
    }
} 