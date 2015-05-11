<?php

namespace Propel\Runtime\Session;

use Propel\Runtime\Configuration;
use Propel\Runtime\EntityProxyInterface;
use Propel\Runtime\Event\CommitEvent;
use Propel\Runtime\Event\PersistEvent;
use Propel\Runtime\Events;
use Propel\Runtime\UnitOfWork;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Session
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var object[]
     */
    protected $persistQueue = [];

    protected $removeQueue = [];

    protected $persisted = [];
    protected $removed = [];

    function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    protected function debug($message)
    {
        var_dump($message);
    }

    /**
     * @param string $splId
     *
     * @return object
     */
    public function getEntityById($splId)
    {
        return $this->persistQueue[$splId];
    }

    /**
     * @param object $entity
     * @param boolean $deep Whether all attached entities(relations) should be persisted too.
     */
    public function persist($entity, $deep = false)
    {
        $id = spl_object_hash($entity);
        $this->debug('persist(' . get_class($entity) . ', '. var_export($deep, true) . ')');

        $event = new PersistEvent($this, $entity);
        $this->configuration->getEventDispatcher()->dispatch(Events::PRE_PERSIST, $event);

        if (!isset($this->persistQueue[$id])) {
            $this->persistQueue[$id] = $entity;

            if ($deep) {
                $entityMap = $this->getConfiguration()->getEntityMapForEntity($entity);
                $entityMap->persistDependencies($this, $entity, true);
            }
        }

        unset($this->removeQueue[$id]);

        $this->configuration->getEventDispatcher()->dispatch(Events::PERSIST, $event);
    }

    public function remove($entity)
    {
        $id = spl_object_hash($entity);

        if (isset($this->removed[$id])) {
            throw new \InvalidArgumentException('Entity has already been removed.');
        }

        //if new object and has not been persisted yet, we can not delete it.
        if (!($entity instanceof EntityProxyInterface) && !isset($this->persisted[$id])) {
//            //no proxy and not persisted, make sure its not in persistQueue only.
//            unset($this->persistQueue[$id]);
//            return;
            throw new \InvalidArgumentException('Can not delete. New entity has not been persisted yet.');
        }

        $this->removeQueue[$id] = $entity;
        unset($this->persistQueue[$id]);
    }

    public function commit()
    {
        $event = new CommitEvent($this);
        $this->configuration->getEventDispatcher()->dispatch(Events::PRE_COMMIT, $event);

        $this->doDelete();
        $this->doPersist();

        $this->configuration->getEventDispatcher()->dispatch(Events::COMMIT, $event);
    }

    /**
     * Proxies all queued entities to be deleted to its persister class.
     */
    protected function doDelete()
    {
        $removeGroups = [];
        $persisterMap = [];

        foreach ($this->removeQueue as $entity) {
            $entityClass = get_class($entity);

            if (!isset($persisterMap[$entityClass])) {
                $persister = $this->configuration->getEntityPersisterForEntity($this, $entity);
                $persisterMap[$entityClass] = $persister;
            }

            $removeGroups[$entityClass][] = $entity;
        }

        foreach ($removeGroups as $entities) {
            $entityClass = get_class($entities[0]);
            $persisterMap[$entityClass]->remove($entities);
        }
    }

    /**
     * Proxies all queued entities to be saved to its persister class.
     */
    protected function doPersist()
    {
        $dependencyGraph = new DependencyGraph($this);
        foreach ($this->persistQueue as $entity) {
            $this
                ->configuration
                ->getEntityMapForEntity($entity)
                ->populateDependencyGraph(
                    $entity,
                    $dependencyGraph
                );
        }

        $list = $dependencyGraph->getList();
        $sortedGroups = $dependencyGraph->getGroups();
        $this->debug(sprintf('doPersist(): %d groups, with %d items', count($sortedGroups), count($list)));

        foreach ($sortedGroups as $group) {
            $entityIds = array_slice($list, $group->position, $group->length);
            $firstEntity = $this->persistQueue[$entityIds[0]];
            $persister = $this->configuration->getEntityPersisterForEntity($this, $firstEntity);

            $entities = [];

            foreach ($entityIds as $entityId) {
                $entity = $this->persistQueue[$entityId];
                $entities[] = $entity;
                $this->persisted[$entityId] = true;
            }

            $this->debug(sprintf('Persister::persist() with %d items of %s', $group->length, $group->type));
            $persister->persist($entities);
        }

        $this->persistQueue = [];
        $this->removeQueue = [];
    }
}