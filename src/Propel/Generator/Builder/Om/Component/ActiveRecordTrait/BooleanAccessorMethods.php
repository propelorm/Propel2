<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Model\Field;

/**
 * Add boolean accessors (`isXxx` or `hasXxx`) to ActiveRecord trait.
 *
 * @author  Cristiano Cinotti <cristianocinotti@gmail.com>
 * @package Propel\Generator\Builder\Om\Component\ActiveRecordTrait
 */
class BooleanAccessorMethods extends BuildComponent
{
    public function process()
    {
        foreach ($this->getBuilder()->getEntity()->getFields() as $field) {
            if ($field->isBooleanType()) {
                $this->addMethod($this->getBooleanAccessorName($field), $field->getAccessorVisibility())
                    ->setDescription("Get the value of `{$field->getName()}` field")
                    ->setType('bool')
                    ->setBody("return \$this->{$field->getName()};")
                ;
            }
        }
    }

    /**
     * Returns the name to be used as boolean accessor name
     *
     * @param Field $field
     * @return string
     */
    protected function getBooleanAccessorName(Field $field)
    {
        $name = $field->getCamelCaseName();
        if (!preg_match('/^(?:is|has)(?=[A-Z])/', $name)) {
            $name = 'is' . ucfirst($name);
        }
        return $name;
    }
}
