<?php

namespace Propel\Generator\Builder\Om\Component\Proxy;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the __get method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class MagicGetterMethod extends BuildComponent
{
    public function process()
    {
        $body = '';

        foreach ($this->getEntity()->getFields() as $field) {
            if (!$field->isLazyLoad()) {
                continue;
            }

            $fieldName = $field->getName();
            $loaderMethod = 'load' . ucfirst($fieldName);

            $body .= "
if ('{$fieldName}' === \$name && false === \$this->_{$fieldName}_loaded) {
    \$this->_{$fieldName}_loaded = true;

    if (method_exists(\$this, '$loaderMethod')) {
        \$this->\$name = \$this->$loaderMethod();
    } else {
        \$this->\$name = \$this->_repository->{$loaderMethod}(\$this);
    }
}
";
        }

        $body .= "
return \$this->\$name;
";

        $this->addMethod('__get')
            ->addSimpleParameter('name')
            ->setBody($body);
    }
}