<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getAutoIncrementFieldNames method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetAutoIncrementFieldNamesMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $fields = $this->getEntity()->getAutoIncrementFieldNames();

        $fields = var_export($fields, true);
        $body = "
return $fields;
        ";

        $this->addMethod('getAutoIncrementFieldNames')
            ->setBody($body)
            ->setType('string[]')
            ->setTypeDescription('');
    }
}