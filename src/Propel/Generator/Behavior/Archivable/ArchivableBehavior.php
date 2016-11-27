<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Archivable;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpProperty;
use Propel\Generator\Builder\Om\ActiveRecordTraitBuilder;
use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\NamingTool;

/**
 * Keeps tracks of an ActiveRecord object, even after deletion
 *
 * @author Francois Zaninotto
 */
class ArchivableBehavior extends Behavior
{
    use ComponentTrait;

    // default parameters value
    protected $parameters = array(
        'archive_entity'       => '',
        'archive_table'        => null,
        'log_archived_at'     => 'true',
        'archived_at_field'   => 'archivedAt',
        'archive_on_insert'   => 'false',
        'archive_on_update'   => 'false',
        'archive_on_delete'   => 'true',
    );

    /**
     * @var Entity
     */
    protected $archiveEntity;
    protected $objectBuilderModifier;
    protected $queryBuilderModifier;

    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getEntities() as $entity) {
            if ($entity->hasBehavior($this->getId())) {
                // don't add the same behavior twice
                continue;
            }
            if (property_exists($entity, 'isArchiveEntity')) {
                // don't add the behavior to archive entities
                continue;
            }
            $b = clone $this;
            $entity->addBehavior($b);
        }
    }

    public function modifyEntity()
    {
        $this->addArchiveEntity();
    }

    protected function addArchiveEntity()
    {
        $entity = $this->getEntity();
        $database = $entity->getDatabase();
        $archiveEntityName = $this->getParameter('archive_entity');

        if (!$archiveEntityName && $tableName = $this->getParameter('archive_table')) {
            $archiveEntityName = ucfirst(NamingTool::toCamelCase($tableName));
        }

        if (!$archiveEntityName) {
            $archiveEntityName = $this->getEntity()->getName() . 'Archive';
        }

        if (!$database->hasEntity($archiveEntityName)) {
            // create the version entity
            $archiveEntity = $database->addEntity(
                array(
                    'name' => $archiveEntityName,
                    'tableName' => $this->getParameter('archive_table'),
                    'package' => $entity->getPackage(),
                    'schema' => $entity->getSchema(),
                    'activeRecord' => $entity->getActiveRecord(),
                    'namespace' => $entity->getNamespace() ? '\\' . $entity->getNamespace() : null,
                )
            );

            // copy all the fields
            foreach ($entity->getFields() as $field) {
                $fieldInArchiveEntity = clone $field;
                if ($fieldInArchiveEntity->hasReferrers()) {
                    $fieldInArchiveEntity->clearReferrers();
                }
                if ($fieldInArchiveEntity->isAutoincrement()) {
                    $fieldInArchiveEntity->setAutoIncrement(false);
                }
                $archiveEntity->addField($fieldInArchiveEntity);
            }
        } else {
            $archiveEntity = $database->getEntity($archiveEntityName);
        }

        $archiveEntity->isArchiveEntity = true;

        // add archived_at field
        if ('true' === $this->getParameter('log_archived_at')) {
            $archiveEntity->addField(array(
                'name' => $this->getParameter('archived_at_field'),
                'type' => 'TIMESTAMP'
            ));
        }

        // do not copy foreign keys
        // copy the indices
        foreach ($entity->getIndices() as $index) {
            if (!$archiveEntity->isIndex($index->getFieldObjects())) {
                $copiedIndex = clone $index;
                $archiveEntity->addIndex($copiedIndex);
            }
        }
        // copy unique indices to indices
        // see https://github.com/propelorm/Propel/issues/175 for details
        foreach ($entity->getUnices() as $unique) {
            $index = new Index();
            $index->setEntity($entity);
            foreach ($unique->getFields() as $fieldName) {
                if ($size = $unique->getFieldSize($fieldName)) {
                    $index->addField(array('name' => $fieldName, 'size' => $size));
                } else {
                    $index->addField(array('name' => $fieldName));
                }
            }

            if (!$archiveEntity->isIndex($index->getFieldObjects())) {
                $archiveEntity->addIndex($index);
            }
        }

        // every behavior adding a entity should re-execute database behaviors
        foreach ($database->getBehaviors() as $behavior) {
            $behavior->modifyDatabase();
        }

        $this->archiveEntity = $archiveEntity;
    }

    /**
     * @param RepositoryBuilder $builder
     */
    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
        $archiveExcludePersist = new PhpProperty('archiveExcludePersist');
        $archiveExcludePersist->setType('array');
        $archiveExcludePersist->setValue(PhpConstant::create('[]'));
        $builder->getDefinition()->setProperty($archiveExcludePersist);

        $archiveExcludeDelete = clone $archiveExcludePersist;
        $archiveExcludeDelete->setName('archiveExcludeDelete');
        $builder->getDefinition()->setProperty($archiveExcludeDelete);

        $this->applyComponent('Repository\\ArchiveMethod', $builder);
        $this->applyComponent('Repository\\GetArchiveMethod', $builder);
        $this->applyComponent('Repository\\RestoreFromArchiveMethod', $builder);
        $this->applyComponent('Repository\\PersistWithoutArchiveMethod', $builder);
        $this->applyComponent('Repository\\DeleteWithoutArchiveMethod', $builder);
        $this->applyComponent('Repository\\PopulateFromArchiveMethod', $builder);
    }

    public function activeRecordTraitBuilderModification(ActiveRecordTraitBuilder $builder)
    {
        $this->applyComponent('ActiveRecordTrait\\GetArchiveMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\\ArchiveMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\\RestoreFromArchiveMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\\PopulateFromArchiveMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\\SaveWithoutArchiveMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\\DeleteWithoutArchiveMethod', $builder);
    }

    /**
     * @return Entity
     */
    public function getArchiveEntity()
    {
        return $this->archiveEntity;
    }

    public function preDelete(RepositoryBuilder $builder)
    {
        if (!$this->isArchiveOnDelete()) {
            return null;
        }

        return "
foreach(\$event->getEntities() as \$entity) {
    if (isset(\$this->archiveExcludeDelete[spl_object_hash(\$entity)])) {
        continue;
    }
    \$this->archive(\$entity);
    unset(\$this->archiveExcludeDelete[spl_object_hash(\$entity)]);
}
";
    }

    public function preUpdate(RepositoryBuilder $builder)
    {
        if (!$this->isArchiveOnUpdate()) {
            return null;
        }

        return "
foreach(\$event->getEntities() as \$entity) {
    if (isset(\$this->archiveExcludePersist[spl_object_hash(\$entity)])) {
        continue;
    }
    \$this->archive(\$entity);
    unset(\$this->archiveExcludePersist[spl_object_hash(\$entity)]);
}
";
    }

    public function postInsert(RepositoryBuilder $builder)
    {
        if (!$this->isArchiveOnInsert()) {
            return null;
        }

        return "
foreach(\$event->getEntities() as \$entity) {
    if (isset(\$this->archiveExcludePersist[spl_object_hash(\$entity)])) {
        continue;
    }
    \$this->archive(\$entity);
    unset(\$this->archiveExcludePersist[spl_object_hash(\$entity)]);
}
";
    }

    /**
     * @return Field
     */
    public function getArchivedAtField()
    {
        if ($this->getArchiveEntity() && 'true' === $this->getParameter('log_archived_at')) {
            return $this->getArchiveEntity()->getField($this->getParameter('archived_at_field'));
        }
    }

    public function isArchiveOnInsert()
    {
        return 'true' === $this->getParameter('archive_on_insert');
    }

    public function isArchiveOnUpdate()
    {
        return 'true' === $this->getParameter('archive_on_update');
    }

    public function isArchiveOnDelete()
    {
        return 'true' === $this->getParameter('archive_on_delete');
    }
}
