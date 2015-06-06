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
class DeleteWithoutArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "
\$this->archiveExcludeDelete[spl_object_hash(\$entity)] = true;
\$this->getConfiguration()->getSession()->persist(\$entity);
";

        $this->addMethod('deleteWithoutArchive')
            ->setDescription('[Archivable] Deletes the object without archiving it.')
            ->addSimpleDescParameter('entity', $this->getEntity()->getFullClassName())
            ->setBody($body);
    }
}