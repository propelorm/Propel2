<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Adds DATABASE_NAME constant.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Constants extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $entity = $this->getEntity();
        $constant = new PhpConstant('DATABASE_NAME');
        $constant->setDescription("The database and connection name for the {$entity->getName()} entity.");
        $constant->setValue($entity->getDatabase()->getName());
        $this->getDefinition()->setConstant($constant);
    }
}