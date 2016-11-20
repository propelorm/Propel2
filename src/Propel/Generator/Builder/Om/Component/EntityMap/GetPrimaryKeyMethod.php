<?php

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RepositoryTrait;

/**
 * Adds the getPrimaryKeyMethod method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPrimaryKeyMethod extends BuildComponent
{
    use NamingTrait;
    use RepositoryTrait;

    public function process()
    {
        $body = '$reader = $this->getPropReader();' . PHP_EOL;

        $pks = $this->getEntity()->getPrimaryKey();

        if (1 === count($pks)) {
            $firstPk = $pks[0];
            $body .= 'return $reader($entity, ' . var_export($firstPk->getName(), true) . ' );';
        } else {
            $body .= '$pk = [];' . PHP_EOL;
            foreach ($pks as $field) {
                $body .= '$pk[] = $reader($entity, ' . var_export($field->getName(), true) . ' );' . PHP_EOL;
            }
            $body .= 'return $pk;';
        }

        $this->addMethod('getPrimaryKey')
            ->addSimpleParameter('entity', 'object')
            ->setType('array|integer|string')
            ->setBody($body);
    }
}