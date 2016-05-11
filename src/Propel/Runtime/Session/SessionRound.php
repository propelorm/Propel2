<?php

namespace Propel\Runtime\Session;

use Propel\Runtime\Configuration;
use Propel\Runtime\EntityProxyInterface;
use Propel\Runtime\Event\CommitEvent;
use Propel\Runtime\Event\PersistEvent;
use Propel\Runtime\Events;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\UnitOfWork;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class SessionRound
{

    /**
     * @var object[]
     */
    protected $persistQueue = [];

    /**
     * @var array
     */
    protected $removeQueue = [];

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var boolean
     */
    protected $inCommit;

    /**
     * @var boolean
     */
    protected $committed = false;

    protected $idx;

    /**
     * @param Session $session
     */
    public function __construct(Session $session, $roundIdx)
    {
        $this->session = $session;
        $this->idx = $roundIdx;
    }

    /**
     * @return mixed
     */
    public function getIdx()
    {
        return $this->idx;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->session->getConfiguration();
    }

    /**
     * @return boolean
     */
    public function isCommitted()
    {
        return $this->committed;
    }

    /**
     * @param object $entity
     * @param boolean $deep Whether all attached entities(relations) should be persisted too.
     */
    public function persist($entity, $deep = false)
    {
        $id = spl_object_hash($entity);
        $this->getConfiguration()->debug('persist(' . get_class($entity) . ', ' . var_export($deep, true) . ')');

        if (!isset($this->persistQueue[$id])) {

            $entityMap = $this->getConfiguration()->getEntityMapForEntity($entity);

            if (!$entityMap->isAllowPkInsert() && $entityMap->hasAutoIncrement() && $this->getSession()->isNew($entity)) {
                //insert PKs is not allowed, so reject it if set
                $propReader = $entityMap->getPropReader();
                $propIsset = $entityMap->getPropIsset();
                foreach ($entityMap->getAutoIncrementFieldNames() as $fieldName) {
                    if ($propIsset($entity, $fieldName) && null !== $propReader($entity, $fieldName)) {
                        throw new PropelException(
                            'Cannot insert a value for auto-increment primary key (' . $fieldName . ') in entity '
                            . $entityMap->getFullClassName()
                        );
                    }
                }
            }

            $event = new PersistEvent($this->getSession(), $entityMap, $entity);
            $this->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_PERSIST, $event);
            $this->persistQueue[$id] = $entity;
            $this->getConfiguration()->getEventDispatcher()->dispatch(Events::PERSIST, $event);

            if ($deep) {
                $entityMap->persistDependencies($this->getSession(), $entity, true);
            }
        }

        unset($this->removeQueue[$id]);
    }

    /**
     * @param $entity
     */
    public function remove($entity)
    {
        $id = spl_object_hash($entity);

        if ($this->getSession()->isRemoved($id)) {
            throw new \InvalidArgumentException('Entity has already been removed.');
        }

        //if new object and has not been persisted yet, we can not delete it.
        if (!($entity instanceof EntityProxyInterface) && !$this->getSession()->isPersisted($id)) {
//            //no proxy and not persisted, make sure its not in persistQueue only.
//            unset($this->persistQueue[$id]);
//            return;
            throw new \InvalidArgumentException('Can not delete. New entity has not been persisted yet.');
        }

        $this->removeQueue[$id] = $entity;
        unset($this->persistQueue[$id]);
    }

    /**
     *
     */
    public function commit()
    {
        if (!$this->removeQueue && !$this->persistQueue) {
            return;
        }

        $this->inCommit = true;

        $event = new CommitEvent($this->getSession());
        $this->getConfiguration()->getEventDispatcher()->dispatch(Events::PRE_COMMIT, $event);

        //create transaction
        try {
            $this->doDelete();
            $this->doPersist();

            //commit
            $this->getConfiguration()->getEventDispatcher()->dispatch(Events::COMMIT, $event);
        } catch (\Exception $e) {
            //rollback
            $this->inCommit = false;
            throw $e;
        }

        $this->committed = true;
        $this->inCommit = false;
    }

    /**
     * Whether this session is currently being in a commit transaction or not.
     *
     * @return bool
     */
    public function isInCommit()
    {
        return $this->inCommit;
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
                $persister = $this->getConfiguration()->getEntityPersisterForEntity($this->getSession(), $entity);
                $persisterMap[$entityClass] = $persister;
            }

            $removeGroups[$entityClass][] = $entity;
        }

        foreach ($removeGroups as $entities) {
            $entityClass = get_class($entities[0]);
            $persisterMap[$entityClass]->remove($entities);
        }

        $this->removeQueue = [];
    }

    /**
     * Proxies all queued entities to be saved to its persister class.
     */
    protected function doPersist()
    {
        $dependencyGraph = new DependencyGraph($this->getSession());
        foreach ($this->persistQueue as $entity) {
            $entityMap = $this->getConfiguration()->getEntityMapForEntity($entity);
            $entityMap->populateDependencyGraph($entity, $dependencyGraph);
        }

        $list = $dependencyGraph->getList();
        $sortedGroups = $dependencyGraph->getGroups();
        $this->getConfiguration()->debug(
            sprintf('doPersist(): %d groups, with %d items', count($sortedGroups), count($list))
        );

        foreach ($sortedGroups as $group) {
            $entityIds = array_slice($list, $group->position, $group->length);
            $firstEntity = $this->persistQueue[$entityIds[0]];
            $persister = $this->getConfiguration()->getEntityPersisterForEntity($this->getSession(), $firstEntity);

            $entities = [];

            foreach ($entityIds as $entityId) {
                $entity = $this->persistQueue[$entityId];
                $entities[] = $entity;
            }
            $this->getConfiguration()->debug(
                sprintf('persist-pre: %d $this->persistQueue', count($this->persistQueue))
            );
            $this->getConfiguration()->debug(
                sprintf('Persister::persist() with %d items of %s', $group->length, $group->type)
            );
            try {
                $persister->persist($entities);
                $this->getConfiguration()->debug(
                    sprintf('persist-post: %d $this->persistQueue', count($this->persistQueue))
                );

                foreach ($entityIds as $entityId) {
                    $entity = $this->persistQueue[$entityId];
                    $this->getSession()->addToFirstLevelCache($entity);
                    $this->getSession()->snapshot($entity);
                }
            } catch (\Exception $e) {
                foreach ($entityIds as $entityId) {
                    $this->getSession()->removePersisted($entityId);
                }
                throw $e;
            }
        }

        $this->persistQueue = [];
    }
}