<?php

namespace Propel\Generator\Behavior\Archivable\Component\ActiveRecordTrait;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class SaveWithoutArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "
\$this->getRepository()->persistWithoutArchive(\$this);
\$this->getPropelConfiguration()->getSession()->commit();
";

        $this->addMethod('saveWithoutArchive')
            ->setDescription('[Archivable] Saves without creating an archive for it.')
            ->setBody($body);
    }
}