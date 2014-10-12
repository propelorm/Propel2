<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Adds addSelectFields method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class AddSelectFieldsMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $this->getDefinition()->declareUse('Propel\Runtime\ActiveQuery\Criteria');

        $body = "
if (null === \$alias) {";

        foreach ($this->getEntity()->getFields() as $field) {
            if (!$field->isLazyLoad()) {
                $body .= "
    \$criteria->addSelectField({$field->getFQConstantName()});";
            }
        }

        $body .= "
} else {";
        foreach ($this->getEntity()->getFields() as $field) {
            if (!$field->isLazyLoad()) {
                $body .= "
    \$criteria->addSelectField(\$alias . '." . $field->getName() . "');";
            }
        }
        $body .= "
}
";

        $this->addMethod('addSelectFields')
            ->addSimpleParameter('criteria', 'Criteria')
            ->addSimpleParameter('alias', 'string', null)
            ->setBody($body);
    }
}