<?php

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentHelperTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;

/**
 * Adds the getPrimaryKey for ActiveRecord interface.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPrimaryKeyMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
return \$this->getRepository()->getEntityMap()->getPrimaryKey(\$this);";

        $this->addMethod('getPrimaryKey')
            ->setDescription('Returns the current primary key')
            ->setType('array|integer|string')
            ->setBody($body);
    }
}