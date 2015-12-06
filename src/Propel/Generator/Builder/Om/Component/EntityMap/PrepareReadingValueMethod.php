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
        $body = <<<EOF
\$fieldType = \$this->getFieldType(\$fieldName);
return \$fieldType->snapshotPHPValue(\$value, \$this->getField(\$fieldName));

EOF;

        $this->addMethod('prepareReadingValue')
            ->addSimpleParameter('value', 'mixed')
            ->addSimpleParameter('fieldName', 'string')
            ->setType('mixed')
            ->setBody($body);
    }
}