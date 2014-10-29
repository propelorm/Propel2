<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds hasAutoIncrement method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class HasAutoIncrementMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $hasAutoIncrement = $this->getEntity()->hasAutoIncrementPrimaryKey() ? 'true' : 'false';

        $body = "
        return $hasAutoIncrement;
        ";

        $this->addMethod('hasAutoIncrement')
            ->setBody($body)
            ->setType('boolean')
            ->setTypeDescription('Whether this entity contains at least one field with auto-increment value.');
    }
}