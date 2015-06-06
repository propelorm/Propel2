<?php

namespace Propel\Generator\Behavior\Archivable\Component\Repository;

use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class RestoreFromArchiveMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "

if (!\$archive = \$this->getArchive(\$entity)) {
    throw new PropelException('The current object has never been archived and cannot be restored');
}
\$this->populateFromArchive(\$entity, \$archive);
";

        $this->addMethod('restoreFromArchive')
            ->setDescription('[Archivable] Revert the the current object to the state it had when it was last archived.
The object must be saved afterwards if the changes must persist.')
            ->addSimpleDescParameter('entity', $this->getEntity()->getFullClassName())
            ->setBody($body);
    }
}