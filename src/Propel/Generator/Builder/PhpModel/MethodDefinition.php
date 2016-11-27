<?php

namespace Propel\Generator\Builder\PhpModel;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpMethod;

class MethodDefinition extends PhpMethod
{
    /**
     * @param string $name
     * @param null   $type
     * @param null   $defaultValue
     *
     * @return $this
     */
    public function addSimpleParameter($name, $type = null, $defaultValue = null) {

        if (2 < func_num_args()) {
            if (is_array($defaultValue)) {
                $defaultValue = PhpConstant::create('[]', null, true);
            }
            return parent::addSimpleParameter($name, $type, $defaultValue);
        }

        return parent::addSimpleParameter($name, $type);
    }
}