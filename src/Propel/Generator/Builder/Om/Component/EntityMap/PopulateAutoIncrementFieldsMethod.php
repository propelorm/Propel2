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
class PopulateAutoIncrementFieldsMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $fields = $this->getEntity()->getAutoIncrementFieldNames();

        $body = '
$reader = $this->getPropReader();
$writer = $this->getPropWriter();
        ';

        foreach ($fields as $fieldName) {
            $body .= "
if (\$value = \$reader(\$entity, '$fieldName')) {
    \$autoIncrementValues->$fieldName = \$value;
} else {
    \$writer(\$entity, '$fieldName', \$autoIncrementValues->$fieldName);
    \$autoIncrementValues->$fieldName++;
}
            ";
        }

        $this->addMethod('populateAutoIncrementFields')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleParameter('autoIncrementValues', 'object')
            ->setBody($body);
    }
}