<?php

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Builder\Om\Component\RepositoryTrait;
use Propel\Generator\Model\Field;

/**
 * Adds the buildPkeyCriteria method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildPkeyCriteriaMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = '
$entityReader = $this->getPropReader();
';

        if (!$this->getEntity()->getPrimaryKey()) {
            $body .= "
throw new LogicException('The {$this->getObjectClassName()} entity has no primary key');";
        } else {
            $body .= "
\$criteria = \$this->getRepository()->createQuery();";

            $entityMapClass = $this->getEntityMapClassName(true);

            foreach ($this->getEntity()->getPrimaryKey() as $field) {
                if ($field->isImplementationDetail()) {
                    continue;
                }

                $fieldName = $field->getName();

                $body .= "
\$criteria->add(\\$entityMapClass::" . $field->getConstantName() . ", \$entityReader(\$entity, '$fieldName'));";
            }


            foreach ($this->getEntity()->getRelations() as $relation) {
                if (!$relation->isLocalPrimaryKey()) {
                    continue;
                }

                $className = $relation->getForeignEntity()->getFullClassName();
                $propertyName = $this->getRelationVarName($relation);

                $body .= "
//relation:$propertyName
\$foreignEntityReader = \$this->getClassPropReader('$className');
\$foreignEntity = \$entityReader(\$entity, '$propertyName');
";
                foreach ($relation->getFieldObjectsMapArray() as $map) {
                    /** @var Field $localField */
                    /** @var Field $foreignField */
                    list ($localField, $foreignField) = $map;
                    $foreignFieldName = $foreignField->getName();

                    $placeholder[] = '?';

                    $body .= "
\$value = null;
if (\$foreignEntity) {
    \$value = \$foreignEntityReader(\$foreignEntity, '{$foreignFieldName}');
    \$criteria->add(\\$entityMapClass::" . $localField->getConstantName() . ", \$value);
}
";
                }

                $body .= "
//end relation:$propertyName";
            }
        }

        $body .= "
return \$criteria;
";


        $this->addMethod('buildPkeyCriteria')
            ->addSimpleParameter('entity', 'object')
            ->setType('Criteria')
            ->setBody($body);
    }
}