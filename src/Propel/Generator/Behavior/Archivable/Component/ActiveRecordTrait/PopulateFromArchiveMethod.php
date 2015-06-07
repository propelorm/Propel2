<?php

namespace Propel\Generator\Behavior\Archivable\Component\ActiveRecordTrait;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PopulateFromArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();
        $archiveClassName = $behavior->getArchiveEntity()->getFullClassName();

        $body = "
\$this->getRepository()->populateFromArchive(\$this, \$archive);
";

        $this->addMethod('populateFromArchive')
            ->setDescription('[Archivable] Populates the object based on a $archive object.')
            ->addSimpleParameter('archive', 'object')
            ->setType($archiveClassName)
            ->setBody($body);
    }
}