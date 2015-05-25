<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RepositoryTrait;

/**
 * Adds the find method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FindMethod extends BuildComponent
{
    use NamingTrait;
    use RepositoryTrait;

    public function process()
    {
        $entityClassName = $this->getObjectClassName();
        $class = $this->getObjectClassName();
        $entityMapClassName = $this->getEntityMapClassName();
        $entity = $this->getEntity();

            $body = "
if (null === \$key) {
    return null;
}";
            if ($entity->hasCompositePrimaryKey()) {
                $pks = array();
                foreach ($entity->getPrimaryKey() as $index => $column) {
                    $pks [] = "\$key[$index]";
                }
            } else {
                $pks = '$key';
            }
            $pkHash = $this->getFirstLevelCacheKeySnippet($pks);
            $body .= "
if ((null !== (\$obj = \$this->getInstanceFromFirstLevelCache('{$entity->getFullClassName()}', {$pkHash})))) {
    // the object is already in the instance pool
    return \$obj;
}

return \$this->doFind(\$key);
";
//        }

        $pkType = $entity->getFirstPrimaryKeyField()->getPhpType();

        if ($entity->hasCompositePrimaryKey()) {
            $pkType = 'array';
        }

        $this->addMethod('find')
            ->addSimpleParameter('key', $pkType)
            ->setType($entityClassName)
            ->setBody($body);
    }
}