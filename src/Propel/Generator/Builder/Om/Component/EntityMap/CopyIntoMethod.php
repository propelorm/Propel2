<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 * Adds copyInto method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CopyIntoMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = "
\$excludeFields = array_flip(\$excludeFields);
\$entityReader = \$this->getPropReader();
\$targetWriter = \$this->getConfiguration()->getEntityMapForEntity(\$target)->getPropWriter();
";

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                continue;
            }

            $body .= "
if (!isset(\$excludeFields['{$field->getName()}'])) {
    \$targetWriter(\$target, '{$field->getName()}', \$entityReader(\$entity, '{$field->getName()}'));
}
";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $fieldName = $this->getRelationVarName($relation);
            $body .= "
if (!isset(\$excludeFields['{$fieldName}'])) {
    \$targetWriter(\$target, '{$fieldName}', \$entityReader(\$entity, '{$fieldName}'));
}";
        }

        $this->addMethod('copyInto')
            ->addSimpleParameter('entity')
            ->addSimpleParameter('target')
            ->addSimpleParameter('excludeFields', 'array', [])
            ->setBody($body);
    }
}