<?php

namespace Propel\Generator\Builder\Om\Component\Proxy;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the __get/__set method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class MagicMethods extends BuildComponent
{
    public function process()
    {

        $body = '
';

        foreach ($this->getEntity()->getFields() as $field) {
            $fieldName = $field->getName();

            $entityLazyLoading = '';
            $fieldLazyLoading = '';

            if ($field->isLazyLoad()) {
                $loadMethod = 'load' . ucfirst($fieldName);

                $fieldLazyLoading = "
    if (method_exists(\$this, '$loadMethod')) {
        \$this->\$name = \$this->$loadMethod();
    } else {
        \$this->\$name = \$this->_repository->{$loadMethod}(\$this);
    }";
            } else {
                $entityLazyLoading = '$this->_repository->load($this);';
            }

            $body .= "
if (!isset(\$this->__duringInitializing__) && '{$fieldName}' === \$name && !isset(\$this->{$fieldName})) {

    \$this->__duringInitializing__ = true;

    $entityLazyLoading
    $fieldLazyLoading

    unset(\$this->__duringInitializing__);
}
";
        }

        $getBody =  $body . "
return \$this->\$name;
";
        $this->addMethod('__get')
            ->addSimpleParameter('name')
            ->setBody($getBody);

        $setBody =  $body . "
\$this->\$name = \$value;
";
        $this->addMethod('__set')
            ->addSimpleParameter('name')
            ->addSimpleParameter('value')
            ->setBody($setBody);
    }
}