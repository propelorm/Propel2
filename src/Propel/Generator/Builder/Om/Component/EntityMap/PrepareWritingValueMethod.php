<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\MysqlPlatform;

class PrepareWritingValueMethod extends BuildComponent
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

        $this->addMethod('prepareWritingValue')
            ->addSimpleParameter('value', 'mixed')
            ->addSimpleParameter('field', 'string')
            ->setType('mixed')
            ->setBody($body);
    }

    protected function getFieldBody(Field $field)
    {
        $body = '';

        if ($field->isTemporalType()) {

            $dateTimeClass = $this->getBuilder()->getBuildProperty('dateTimeClass');
            if (!$dateTimeClass) {
                $dateTimeClass = '\DateTime';
            }

            $body = "
    if (!(\$value instanceof \\DateTime)) {
        \$value = \\Propel\\Runtime\\Util\\PropelDateTime::newInstance(\$value, null, '$dateTimeClass');
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