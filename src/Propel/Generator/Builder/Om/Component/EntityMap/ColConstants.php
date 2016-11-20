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
 * Adds all field column constantso.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ColConstants extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        foreach ($this->getEntity()->getFields() as $field) {
            $constant = new PhpConstant($field->getConstantName());
            $constant->setDescription("The qualified name for the {$field->getName()} field.");
            $constant->setValue($this->getEntity()->getFullClassName() . '.' .$field->getName());

            $this->getDefinition()->setConstant($constant);
        }
    }
}