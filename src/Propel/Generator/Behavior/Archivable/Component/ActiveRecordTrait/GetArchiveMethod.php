<?php

namespace Propel\Generator\Behavior\Archivable\Component\ActiveRecordTrait;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();
        $archiveClassName = $behavior->getArchiveEntity()->getFullClassName();

        $body = "
return \$this->getRepository()->getArchive(\$this);
";

        $this->addMethod('getArchive')
            ->setDescription('[Archivable] returns archived version.')
            ->setType($archiveClassName.'|null')
            ->setBody($body);
    }
}