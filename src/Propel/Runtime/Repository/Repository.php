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
use Propel\Runtime\Session\Session;
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
     * @return Session
     */
    public function getSession()
    {
        return $this->getConfiguration()->getSession();
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