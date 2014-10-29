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

    protected $deleteQueue = [];

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

    /**
     * @param object $entity
     * @param boolean $deep If all attached entities(relations) should be persisted too.
     */
    public function persist($entity, $deep = false)
    {
        $id = spl_object_hash($entity);

        $event = new PersistEvent($this, $entity);
        $this->configuration->getEventDispatcher()->dispatch(Events::PRE_PERSIST, $event);


        if (!isset($this->persistQueue[$id])) {
            $this->persistQueue[$id] = $entity;

            if ($deep) {
                $entityMap = $this->getConfiguration()->getEntityMapForEntity($entity);
                $entityMap->persistDependencies($this, $entity, true);
            }
        }

        $this->configuration->getEventDispatcher()->dispatch(Events::PERSIST, $event);
    }

    public function commit()
    {
        $event = new CommitEvent($this);
        $this->configuration->getEventDispatcher()->dispatch(Events::PRE_COMMIT, $event);

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

        foreach ($sortedGroups as $group) {
            $entityIds = array_slice($list, $group->position, $group->length);
            $firstEntity = $this->persistQueue[$entityIds[0]];
            $persister = $this->configuration->getEntityPersisterForEntity($this, $firstEntity);

            $entities = [];

            foreach ($entityIds as $entityId) {
                $entity = $this->persistQueue[$entityId];
                $entities[] = $entity;
            }

            $persister->persist($entities);
        }

        $this->configuration->getEventDispatcher()->dispatch(Events::COMMIT, $event);

        $this->persistQueue = [];
        $this->deleteQueue = [];
    }
}