<?php

namespace Propel\Runtime\Session;

use Propel\Common\Types\FieldTypeInterface;
use Propel\Runtime\Configuration;
use Propel\Runtime\Events;
use Propel\Runtime\Exception\SessionClosedException;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Session
{
    /**
     * @var SessionRound[]
     */
    protected $rounds;

    /**
     * @var int
     */
    protected $currentRound = -1;

    /**
     * @var bool
     */
    protected $closed = false;

    /**
     * @var int
     */
    protected $currentCommitRound = -1;

    /**
     * @var array
     */
    protected $knownEntities = [];

    /**
     * Array of last known values. This is based on values from propertyToSnapshot.
     *
     * @var array[]
     */
    protected $lastKnownValues = [];

    /**
     * @var boolean
     */
    protected $inCommit = false;

    /**
     * @var array
     */
    protected $persisted = [];

    /**
     * @var array
     */
    protected $removed = [];

    /**
     * @var object[]
     */
    protected $firstLevelCache = [];

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        $self = $this;
        $this->getConfiguration()->getEventDispatcher()->addListener(Events::PRE_SAVE, function() use ($self) {
            //if PRE_SAVE hooks added new rounds, commit those first
            $self->commit();
        }, -128);
        $this->getConfiguration()->getEventDispatcher()->addListener(Events::PRE_INSERT, function() use ($self) {
            //if PRE_SAVE hooks added new rounds, commit those first
            $self->commit();
        }, -128);
        $this->getConfiguration()->getEventDispatcher()->addListener(Events::PRE_UPDATE, function() use ($self) {
            //if PRE_SAVE hooks added new rounds, commit those first
            $self->commit();
        }, -128);
        $this->getConfiguration()->getEventDispatcher()->addListener(Events::PRE_DELETE, function() use ($self) {
            //if PRE_SAVE hooks added new rounds, commit those first
            $self->commit();
        }, -128);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $splId
     *
     * @return object
     */
    public function getEntityById($splId)
    {
        if (!isset($this->knownEntities[$splId])) {
            return null;
        }

        return $this->knownEntities[$splId];
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function isRemoved($id)
    {
        return isset($this->removed[$id]);
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    public function isNew($entity)
    {
        $id = spl_object_hash($entity);
        if ($entity instanceof \Propel\Runtime\EntityProxyInterface) {
            if (isset($this->removed[$id])) {
                //it has been deleted after receiving from the database,
                return true;
            }

            return false;
        } else {
            if (isset($this->persisted[$id])) {
                //it has been committed
                return false;
            }

            return true;
        }
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    public function isChanged($entity)
    {
        if ($this->hasKnownValues($entity)) {
            return !!$this->getConfiguration()->getRepositoryForEntity($entity)->buildChangeSet($entity);
        }

        return false;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function isPersisted($id)
    {
        return isset($this->persisted[$id]);
    }

    /**
     * @param string $id
     */
    public function setPersisted($id)
    {
        $this->persisted[$id] = true;
    }

    /**
     * @param string $id
     */
    public function removePersisted($id)
    {
        unset($this->persisted[$id]);
    }

    /**
     * @return SessionRound
     */
    public function getCurrentRound()
    {
        if (-1 === $this->currentRound) {
            $this->enterNewRound();
        }

        return $this->rounds[$this->currentRound];
    }

    /**
     * @param $entity
     */
    public function remove($entity)
    {
        if ($this->getCurrentRound()->isInCommit()) {
            $this->enterNewRound();
        }

        $this->getCurrentRound()->remove($entity);
    }

    /**
     * @return SessionRound
     */
    public function enterNewRound()
    {
        $this->currentRound++;
        $this->getConfiguration()->debug('enter new round (' . $this->currentRound . ')');

        return $this->rounds[$this->currentRound] = new SessionRound($this, $this->currentRound);
    }

//    /**
//     *
//     */
//    public function closeRound()
//    {
//        if ($this->hasOpenRounds()) {
//            $this->getConfiguration()->debug('close round (' . $this->currentRound . ')');
//            unset($this->rounds[$this->currentRound]);
//            $this->currentRound--;
//        }
//    }

//    /**
//     * @return bool
//     */
//    public function hasOpenRounds()
//    {
//        return $this->currentRound >= 0;
//    }

    /**
     * @return int
     */
    public function getCurrentCommitRoundIndex()
    {
        return $this->currentCommitRound;
    }

    /**
     * @param object $entity
     * @param boolean $deep Whether all attached entities(relations) should be persisted too.
     */
    public function persist($entity, $deep = false)
    {
        if ($this->getCurrentRound()->isInCommit()) {
            $this->enterNewRound();
        }

        $this->knownEntities[spl_object_hash($entity)] = $entity;
        $this->getCurrentRound()->persist($entity, $deep);
    }

    /**
     * @throws \Exception
     */
    public function commit()
    {
        if (!$this->rounds) {
            return;
        }

        if ($this->closed) {
            throw new SessionClosedException('Session is closed due to an exception. Repair its failure and call reset() to open it again.');
        }

        $allCommitted = false;

        while (true) {
            $allCommitted = true;
            $allBusy = true;

            foreach ($this->rounds as $idx => $round) {
                $allCommitted &= $round->isCommitted();
                if (!$round->isCommitted()) {
                    $allBusy &= $round->isInCommit();
                }
                if (!$round->isCommitted() && !$round->isInCommit()) {
//                    $this->currentCommitRound = $idx;
                    $this->getConfiguration()->debug('commit round (' . $idx . ')');
                    try {
                        $round->commit();
                    } catch (\Exception $e) {
                        $this->getConfiguration()->debug('force close session');
                        $this->closed = true;
                        throw $e;
                    }
                    $this->getConfiguration()->debug('close round (' . $idx . ')');
                    continue;
                }
            }

            if ($allCommitted || $allBusy) {
                break;
            }
        }

        if ($allCommitted) {
            $this->getConfiguration()->debug('close all rounds. ' . count($this->rounds) . ' rounds committed');
            $this->currentRound = -1;
            $this->rounds = [];
        }
    }

    /**
     * @return boolean
     */
    public function isClosed()
    {
        return $this->closed;
    }

    /**
     * Opens the session again and resets all rounds.
     */
    public function reset()
    {
        $this->closed = false;
        $this->currentRound = -1;
        $this->rounds = [];
        $this->lastKnownValues = [];
        $this->firstLevelCache = [];
    }

    /**
     * Reads all values of $entity and place it in lastKnownValues.
     *
     * @param object $entity
     *
     * @return array
     */
    public function snapshot($entity)
    {
        $values = $this->getConfiguration()->getEntityMapForEntity($entity)->getSnapshot($entity);

        return $this->lastKnownValues[spl_object_hash($entity)] = $values;
    }

    /**
     * Returns last known values by the database as snapshot values.
     *
     * @see FieldTypeInterface::propertyToSnapshot()
     *
     * Use FieldTypeInterface::snapshotToProperty() to make sure you have the real php value.
     *
     * Those values are currently known by the database and should be used to query the
     * database when you need to work with primary keys. You may want to use getOriginPK().
     *
     * That is important for event hooks: since objects in preSave event for example
     * can contain changed primary keys. If you'd use such a primary key directly, you could not use this value
     * in queries, since those primary keys might not be known by the database yet. getLastKnownValues returns always
     * the single truth about the current state of the database.
     *
     * @param object|string $id
     * @param bool          $orCurrent
     *
     * @return array
     */
    public function getLastKnownValues($id, $orCurrent = false)
    {
        if (is_object($id)) {

            if ($orCurrent && !$this->hasKnownValues($id)) {
                return $this->getConfiguration()->getEntityMapForEntity($id)->getSnapshot($id);
            }

            $id = spl_object_hash($id);
        }

        if (!isset($this->lastKnownValues[$id])) {
            throw new \InvalidArgumentException(
                'Given id does not exist in known values pool. Create a snapshot(), use $orCurrent=true or use hasKnownValues().'
            );
        }

        return $this->lastKnownValues[$id];
    }

    /**
     * @param object|string $id
     *
     * @return bool
     */
    public function hasKnownValues($id)
    {
        if (is_object($id)) {
            $id = spl_object_hash($id);
        }

        return isset($this->lastKnownValues[$id]);
    }

    /**
     * Contains all values of an object as snapshots.
     *
     * This is being used to build a change set.
     *
     * @see FieldTypeInterface::propertyToSnapshot()
     *
     * @param object|string $id
     * @param array         $values
     */
    public function setLastKnownValues($id, $values)
    {
        if (is_object($id)) {
            $id = spl_object_hash($id);
        }

        $this->lastKnownValues[$id] = $values;
    }

    /**
     * @param string $hashCode
     *
     * @return object
     */
    public function getInstanceFromFirstLevelCache($prefix, $hashCode)
    {
        if (isset($this->firstLevelCache[$prefix][$hashCode])) {
            $this->getConfiguration()->debug('retrieve firstLevelCache ' . $prefix . '/' . $hashCode);

            return $this->firstLevelCache[$prefix][$hashCode];
        } else {
            $this->getConfiguration()->debug('rejected firstLevelCache ' . $prefix . '/' . $hashCode);
        }
    }

    /**
     * @param object $entity
     */
    public function addToFirstLevelCache($entity)
    {
        $repo = $this->getConfiguration()->getRepositoryForEntity($entity);
        $prefix = $repo->getEntityMap()->getFullClassName();

        $originPk = json_encode($repo->getOriginPK($entity));
        $currentPk = json_encode($repo->getPK($entity));

        if (!isset($this->firstLevelCache[$prefix])) {
            $this->firstLevelCache[$prefix] = [];
        }

        if ($originPk !== $currentPk) {
            if (isset($this->firstLevelCache[$prefix][$originPk]) && $this->firstLevelCache[$prefix][$originPk] === $entity) {
                unset($this->firstLevelCache[$prefix][$originPk]);
            }
        }

        $this->getConfiguration()->debug('new firstLevelCache ' . $prefix . '/' . $currentPk);
        $this->firstLevelCache[$prefix][$currentPk] = $entity;
    }

    /**
     * Clears the first level cache completely
     *
     * @param null $prefix
     */
    public function clearFirstLevelCache($prefix = null)
    {
        if ($prefix) {
            unset($this->firstLevelCache[$prefix]);
        } else {
            $this->firstLevelCache = [];
        }
    }
} 