<?php

namespace Propel\Generator\Behavior\ConcreteInheritance\Component;

use Propel\Generator\Behavior\ConcreteInheritance\ConcreteInheritanceBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class OverwritePrimaryKeyGetter extends BuildComponent
{
    use RelationTrait;

    public function process(Relation $parentRelation)
    {
        /** @var ConcreteInheritanceBehavior $behavior */
        $behavior = $this->getBehavior();
        $parentEntity = $behavior->getParentEntity();

        $parentGetter = 'get' . $this->getRelationPhpName($parentRelation);

        foreach ($parentEntity->getPrimaryKey() as $primaryKey) {
            $methodName = 'get' . $primaryKey->getMethodName();

            $body = "
return \$this->{$parentGetter}()->{$methodName}();
";
            $method = $this->getDefinition()->hasMethod($methodName) ? $this->getDefinition()->getMethod($methodName) : $this->addMethod($methodName);
            $method->setBody($body);
        }

    }
}