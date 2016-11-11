<?php

namespace Propel\Runtime\Repository;

use Propel\Runtime\Configuration;
use Propel\Runtime\Event\DeleteEvent;
use Propel\Runtime\Event\InsertEvent;
use Propel\Runtime\Event\RepositoryEvent;
use Propel\Runtime\Event\SaveEvent;
use Propel\Runtime\Event\UpdateEvent;
use Propel\Runtime\Events;
use Propel\Runtime\Map\EntityMap;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
abstract class Repository
{

    /**
     * @var EntityMap
     */
    protected $entityMap;

//    /**
//     * All committed object IDs get a new key in this array.
//     *
//     *     $committedIds[spl_object_hash($entity)] = true;
//     *
//     * @var string[]
//     */
//    protected $committedIds = [];
//    protected $deletedIds = [];

    /**
     * Last known values which are as far as we know the latest values in the database.
     * ->snapshot() refreshes this values.
     *
     * @var array[]
     */
    protected $lastKnownValues = [];

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var object[]
     */
    protected $firstLevelCache;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EntityMap     $entityMap
     * @param Configuration $configuration
     */
    public function __construct(EntityMap $entityMap, Configuration $configuration)
    {
        $this->entityMap = $entityMap;
        $this->configuration = $configuration;
        $this->mapEvents();
    }

    /**
     * Maps all event dispatcher events to local event dispatcher, if we have a child class (custom repository class).
     */
    protected function mapEvents()
    {
        $self = $this;
        $mapEvents = [
            Events::PRE_SAVE => 'preSave',
            Events::PRE_UPDATE => 'preUpdate',
            Events::PRE_INSERT => 'preInsert',
            Events::PRE_DELETE => 'preDelete',
            Events::SAVE => 'postSave',
            Events::UPDATE => 'postUpdate',
            Events::INSERT => 'postInsert',
            Events::DELETE => 'postDelete'
        ];

        foreach ($mapEvents as $eventName => $method) {
            $this->configuration->getEventDispatcher()->addListener(
                $eventName,
                function (RepositoryEvent $event) use ($self, $eventName, $method) {
                    if ($self->getEntityMap()->getFullClassName() === $event->getEntityMap()->getFullClassName()) {
                        $self->$method($event);
                        $self->getEventDispatcher()->dispatch($eventName, $event);
                    }
                }
            );
        }
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return boolean
     */
    public function isFieldModified($entity, $fieldName)
    {
        $reader = $this->getEntityMap()->getPropReader();
        $currentValue = $reader($entity, $fieldName);

        if (!$this->hasKnownValues($entity)) {
            //it's a not committed entity, see if its value is different that its default
            $defaultValue = $this->getEntityMap()->getField($fieldName)->getDefaultValue();

            return $defaultValue != $currentValue;
        }

        $oldValues = $this->getLastKnownValues($entity);

        return $this->getEntityMap()->propertyToSnapshot($currentValue, $fieldName) !== $oldValues[$fieldName];
    }

    /**
     * @return mixed
     */
    abstract function createObject();

    /**
     * @return EntityMap
     */
    public function getEntityMap()
    {
        return $this->entityMap;
    }

    /**
     * @param EntityMap $entityMap
     */
    public function setEntityMap($entityMap)
    {
        $this->entityMap = $entityMap;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param object $entity
     *
     * @return array|false Returns false when no changes are detected
     */
    abstract public function buildChangeSet($entity);

    /**
     * @param string   $eventName
     * @param callable $listener
     */
    public function on($eventName, callable $listener)
    {
        $this->getEventDispatcher()->addListener($eventName, $listener);
    }

    /**
     * Deletes all objects from the backend and clears the first level cache.
     */
    public function deleteAll()
    {
        $this->doDeleteAll();
    }

    /**
     * This is the actual implementation of `deleteAll`, which will be provided
     * by the platform.
     */
    abstract protected function doDeleteAll();

    /**
     * @param object $entity
     * @param bool   $deep
     */
    public function persist($entity, $deep = false)
    {
        $this->getConfiguration()->getSession()->persist($entity, $deep);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    public function persistDependencies($entity)
    {
        //not implemented here
    }

    /**
     * @todo, to improve performance pre-compile this stuff
     *
     * @param object $entity
     *
     * @return array
     */
    public function getOriginPK($entity)
    {
        $entityMap = $this->getEntityMap();
        $primaryKeyFields = $entityMap->getPrimaryKeys();
        $singlePk = 1 === count($primaryKeyFields);

        $pk = null;

        $lastKnownValues = $this->getConfiguration()->getSession()->getLastKnownValues($entity, true);
        if ($singlePk) {
            $primaryKeyField = current($primaryKeyFields);
            $pk = $entityMap->snapshotToProperty(
                $lastKnownValues[$primaryKeyField->getName()],
                $primaryKeyField->getName()
            );
        } else {
            $pk = [];
            foreach ($primaryKeyFields as $primaryKeyField) {
                $pks[] = $entityMap->snapshotToProperty(
                    $lastKnownValues[$primaryKeyField->getName()],
                    $primaryKeyField->getName()
                );
            }
        }

        return $pk;
    }

    /**
     * @todo, to improve performance pre-compile this stuff
     *
     * @param array $entities
     *
     * @return array
     */
    public function getOriginPKs(array $entities)
    {
        $pks = [];
        $entityMap = $this->getEntityMap();
        $primaryKeyFields = $entityMap->getPrimaryKeys();
        $singlePk = 1 === count($primaryKeyFields);

        foreach ($entities as $entity) {
            $lastKnownValues = $this->getConfiguration()->getSession()->getLastKnownValues($entity, true);
            $pk = [];

            if ($singlePk) {
                $primaryKeyField = current($primaryKeyFields);
                $pk = $entityMap->snapshotToProperty(
                    $lastKnownValues[$primaryKeyField->getName()],
                    $primaryKeyField->getName()
                );
            } else {
                foreach ($primaryKeyFields as $primaryKeyField) {
                    $pks[] = $entityMap->snapshotToProperty(
                        $lastKnownValues[$primaryKeyField->getName()],
                        $primaryKeyField->getName()
                    );
                }
            }

            $pks[] = $pk;
        }

        return $pks;
    }

    /**
     * @param object $entity
     * @param bool   $orCreate
     *
     * @return array
     */
    public function getLastKnownValues($entity, $orCreate = false)
    {
        return $this->getConfiguration()->getSession()->getLastKnownValues($entity, $orCreate);
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    public function hasKnownValues($entity)
    {
        return $this->getConfiguration()->getSession()->hasKnownValues($entity);
    }

    abstract public function createProxy();

    public function getReference()
    {
        $object = $this->createProxy();
        $writer = $this->getEntityMap()->getPropWriter();
        $pks = func_get_args();

        $i = 0;
        foreach ($this->getEntityMap()->getPrimaryKeys() as $fieldMap) {
            $writer($object, $fieldMap->getName(), $pks[$i++]);
        }

        return $object;
    }

    /**
     * Returns the primary key values from the object as array or directly when the
     * entity has only one primary key.
     *
     * @param object $entity
     *
     * @return array
     */
    public function getPK($entity)
    {
        $reader = $this->getEntityMap()->getPropReader();
        $primaryKeyFields = $this->getEntityMap()->getPrimaryKeys();
        $singlePk = 1 === count($primaryKeyFields);

        $pk = null;
        $normalizedValues = $this->getEntityMap()->getSnapshot($entity);

        if ($singlePk) {
            $primaryKeyField = current($primaryKeyFields);
            $pk = $normalizedValues[$primaryKeyField->getName()];
        } else {
            $pk = [];
            foreach ($primaryKeyFields as $primaryKeyField) {
                $pks[] = $normalizedValues[$primaryKeyField->getName()];
            }
        }

        return $pk;
    }

    protected function preSave(SaveEvent $event)
    {
    }

    protected function preUpdate(UpdateEvent $event)
    {
    }

    protected function preInsert(InsertEvent $event)
    {
    }

    protected function preDelete(DeleteEvent $event)
    {
    }

    protected function postSave(SaveEvent $event)
    {
    }

    protected function postUpdate(UpdateEvent $event)
    {
    }

    protected function postInsert(InsertEvent $event)
    {
    }

    protected function postDelete(DeleteEvent $event)
    {
    }

    /**
     * This is the actual implementation of `find`, which will be provided
     * by the platform.
     *
     * @param array|string|integer $key
     *
     * @return object
     */
    abstract protected function doFind($key);
}