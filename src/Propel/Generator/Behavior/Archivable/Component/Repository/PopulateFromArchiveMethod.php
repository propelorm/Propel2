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
class PopulateFromArchiveMethod extends BuildComponent
{
    public function process()
    {
        /** @var ArchivableBehavior $behavior */
        $behavior = $this->getBehavior();
        $archiveClassName = $behavior->getArchiveEntity()->getFullClassName();
        $this->getDefinition()->declareUse($archiveClassName);

        $body = "
\$this->getConfiguration()->getEntityMap('$archiveClassName')->copyInto(\$archive, \$entity);
";

        $this->addMethod('populateFromArchive')
            ->setDescription('[Archivable] Populates the $entity object based on a $archive object.')
            ->addSimpleDescParameter('entity', $this->getEntity()->getFullClassName())
            ->addSimpleDescParameter('archive', $archiveClassName)
            ->setBody($body);
    }
}