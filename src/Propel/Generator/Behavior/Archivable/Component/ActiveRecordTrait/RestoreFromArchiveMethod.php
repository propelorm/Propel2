<?php

namespace Propel\Generator\Behavior\Archivable\Component\ActiveRecordTrait;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class RestoreFromArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();
        $archiveClassName = $behavior->getArchiveEntity()->getFullClassName();

        $body = "
\$this->getRepository()->restoreFromArchive(\$this, true);
";

        $this->addMethod('restoreFromArchive')
            ->setDescription('[Archivable] Revert the the current object to the state it had when it was last archived.
The object must be saved afterwards if the changes must persist.')
            ->setType($archiveClassName)
            ->setBody($body);
    }
}