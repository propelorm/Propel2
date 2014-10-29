<?php

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentHelperTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;

/**
 * Adds the save method for ActiveRecord interface.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class SaveMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
{$this->getRepositoryAssignment()}
\$repository->save(\$this);";

        $this->addMethod('save')
            ->setDescription('Saves the entity and all it relations immediately')
            ->setBody($body);
    }
}