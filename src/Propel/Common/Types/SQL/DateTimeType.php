<?php

namespace Propel\Common\Types\SQL;

use gossi\codegen\model\PhpMethod;
use Propel\Common\Types\AbstractType;
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Map\FieldMap;
use Propel\Runtime\Util\PropelDateTime;

class DateTimeType extends AbstractType implements BuildableFieldTypeInterface
{
    public function decorateGetterMethod(PhpMethod $method, Field $field)
    {
        $varName = $field->getName();
        $method->addSimpleParameter('format', 'string', null);

        $body = <<<EOF
if (\$format && \$this->{$varName} instanceof \\DateTime) {
    return \$this->{$varName}->format(\$format);
}

return \$this->{$varName};
EOF;
        $method->setBody($body);
    }

    /**
     * {@inheritdoc}
     */
    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if ($value instanceof \DateTime) {
            $format = 'U';

            $adapter = $fieldMap->getEntity()->getAdapter();

            if ($fieldMap->getType() === PropelTypes::DATE) {
                $format = $adapter->getDateFormatter();
            } elseif ($fieldMap->getType() === PropelTypes::TIME) {
                $format = $adapter->getTimeFormatter();
            } elseif ($fieldMap->getType() === PropelTypes::TIMESTAMP) {
                $format = $adapter->getTimestampFormatter();
            }

            return $value->format($format);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        if (!($value instanceof \DateTime)) {
            $value = PropelDateTime::newInstance($value);
        }

        return $value;
    }

    public function build(AbstractBuilder $builder, Field $field)
    {
        if ($builder instanceof ObjectBuilder) {
            $property = $builder->getDefinition()->getProperty($field->getName());

            if ($field->hasDefaultValue()) {

                if ($field->getDefaultValue()->isExpression() && strtoupper($field->getDefaultValue()->getValue()) === 'CURRENT_TIMESTAMP') {
                    $property->unsetExpression();
                    $property->unsetValue();
                }
            }
        }
    }
}
