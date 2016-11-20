<?php

namespace Propel\Generator\Behavior\ConcreteInheritance\Component;

use Propel\Generator\Behavior\ConcreteInheritance\ConcreteInheritanceBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PreSave extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        /** @var ConcreteInheritanceBehavior $behavior */
        $behavior = $this->getBehavior();
        $parentEntity = $behavior->getParentEntity();

        $entityClass = $parentEntity->getFullClassName();
        $getter = 'get' . $parentEntity->getName();
        $setter = 'set' . $parentEntity->getName();

        $allowInsertPk = true;

        if ($parentEntity->hasAutoIncrement()) {
            $allowInsertPk = $parentEntity->isAllowPkInsert();
        }

        $code = <<<EOF
\$session = \$this->getConfiguration()->getSession();
/** @var \\{$this->getRepositoryClassNameForEntity($parentEntity, true)} \$parentRepository */
\$parentRepository = \$this->getConfiguration()->getRepository('$entityClass');
\$reader = \$this->getEntityMap()->getPropReader();

/** @var \\{$this->getEntity()->getFullClassName()} \$entity */
foreach (\$event->getEntities() as \$entity) {

    if (!\$entity->$getter()) {
        \$entity->$setter(\$parentRepository->getEntityMap()->createObject());
    }

    \$parent = \$entity->$getter();

    \$excludeFields = [];
EOF;

        if ($allowInsertPk) {
            foreach ($parentEntity->getPrimaryKey() as $primaryKey) {
                if (!$primaryKey->isAutoIncrement()) {
                    continue;
                }

                $getter = 'get' . $primaryKey->getMethodName();
                $code .= "
    if (null === \$reader(\$entity, '{$primaryKey->getName()}')) {
        \$excludeFields[] = '{$primaryKey->getName()}';
    }
";
            }
        } else {
            $fields = var_export($parentEntity->getAutoIncrementFieldNames(), true);
            $code .= "
    \$excludeFields = $fields;
";
        }

        $code .= <<<EOF
    \$parentRepository->getEntityMap()->copyInto(\$entity, \$parent, \$excludeFields);
    \$parent->set{$parentEntity->getField($behavior->getParameter('descendant_field'))->getMethodName()}('{$behavior->getEntity()->getFullClassName()}');
    \$session->persist(\$parent, true);
}
EOF;

        return $code;
    }
}
