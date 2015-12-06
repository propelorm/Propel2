<?php

namespace Propel\Common\Types;

use gossi\codegen\model\PhpMethod;
use Propel\Generator\Model\Field;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
abstract class AbstractType
{
    public function decorateGetterMethod(PhpMethod $method, Field $field)
    {
        $varName = $field->getName();

        $body = <<<EOF
return \$this->{$varName};
EOF;
        $method->setBody($body);
    }
}