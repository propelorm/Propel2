<?php

namespace Propel\Common\Types\SQL;

use gossi\codegen\model\PhpMethod;
use Propel\Common\Types\AbstractType;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Map\FieldMap;

class DateTimeType extends AbstractType
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

    public function databaseToProperty($value, FieldMap $fieldMap)
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

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        return $this->databaseToProperty($value, $fieldMap);
    }
}