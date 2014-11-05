<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\MysqlPlatform;

/**
 *
 * Adds prepareWritingValue method to be used for data converting from php object -> database.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PrepareReadingValueMethod extends BuildComponent
{
    public function process()
    {
        $body = '';

        foreach ($this->getEntity()->getFields() as $field) {
            $fieldName = $field->getName();

            $fieldBody = $this->getFieldBody($field);

            if ($fieldBody) {
                $body .= "
if (\$field === '$fieldName') {
    $fieldBody
}
";
            }
        }

        $body .= '
return $value;
';

        $this->addMethod('prepareReadingValue')
            ->addSimpleParameter('value', 'mixed')
            ->addSimpleParameter('field', 'string')
            ->setType('mixed')
            ->setBody($body);
    }

    protected function getFieldBody(Field $field)
    {
        $body = '';

        if ($field->isTemporalType()) {
            $format = $this->getTemporalFormatter($field);
            $body = "
    if (\$value instanceof \\DateTime) {
        \$value = \$value->format('$format');
    }
";
        } else if ($field->isLobType()) {
            $body = "
    if (is_resource(\$value)) {
        \$value = stream_get_contents(\$value);
    } else {
        \$value = (string) \$value;
    }
";
        }

        return $body;
    }

    /**
     * Returns the appropriate formatter (from platform) for a date/time column.
     *
     * @param  Field $field
     *
     * @return string
     */
    protected function getTemporalFormatter(Field $field)
    {
        $fmt = null;
        if ($field->getType() === PropelTypes::DATE) {
            $fmt = $this->getPlatform()->getDateFormatter();
        } elseif ($field->getType() === PropelTypes::TIME) {
            $fmt = $this->getPlatform()->getTimeFormatter();
        } elseif ($field->getType() === PropelTypes::TIMESTAMP) {
            $fmt = $this->getPlatform()->getTimestampFormatter();
        }

        return $fmt;
    }
}