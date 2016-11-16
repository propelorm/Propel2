<?php

namespace Propel\Common\Types;

use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Model\Field;

interface BuildableFieldTypeInterface
{
    /**
     * Allows you to modify the generated class of $builder. Use "$builder instanceof ObjectBuilder" to check which builder
     * you got.
     *
     * @param AbstractBuilder $builder
     * @param Field $field
     */
    public function build(AbstractBuilder $builder, Field $field);
}