<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RepositoryTrait;

/**
 * Adds the buildPkeyCriteria method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildPkeyCriteriaMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = '$reader = $this->getEntityMap()->getPropReader();' . PHP_EOL;

        if (!$this->getEntity()->getPrimaryKey()) {
            $body .= "
throw new LogicException('The {$this->getObjectClassName()} entity has no primary key');";
        } else {
            $body .= "
\$criteria = \$this->createQuery();";

            $entityMapClass = $this->getEntityMapClassName(true);

            foreach ($this->getEntity()->getPrimaryKey() as $field) {
                $fieldName = $field->getName();
                $body .= "
\$criteria->add(\\$entityMapClass::" . $field->getConstantName() . ", \$reader(\$entity, '$fieldName'));";
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