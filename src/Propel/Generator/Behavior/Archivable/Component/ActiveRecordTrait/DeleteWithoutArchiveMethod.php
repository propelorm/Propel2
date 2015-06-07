<?php

namespace Propel\Generator\Behavior\Archivable\Component\ActiveRecordTrait;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class DeleteWithoutArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "
\$this->getRepository()->deleteWithoutArchive(\$this);
\$this->getPropelConfiguration()->getSession()->commit();
";

        $this->addMethod('deleteWithoutArchive')
            ->setDescription('[Archivable] Deletes without creating an archive for it.')
            ->setBody($body);
    }
}