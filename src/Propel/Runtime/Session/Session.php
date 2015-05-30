<?php

namespace Propel\Runtime\Session;

use Propel\Runtime\Configuration;

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
     * @var array
     */
    protected $knownEntities = [];

    /**
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
     * @param object  $entity
     * @param boolean $deep Whether all attached entities(relations) should be persisted too.
     */
    public function persist($entity, $deep = false)
    {
        if ($this->getCurrentRound()->inCommit()) {
            $this->enterNewRound();
        }

        $this->knownEntities[spl_object_hash($entity)] = $entity;
        $this->getCurrentRound()->persist($entity, $deep);
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
        if ($this->getCurrentRound()->inCommit()) {
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

        return $this->rounds[$this->currentRound] = new SessionRound($this);
    }

    /**
     *
     */
    public function closeRound()
    {
        if ($this->currentRound >= 0) {
            $this->getConfiguration()->debug('close round (' . $this->currentRound . ')');
            unset($this->rounds[$this->currentRound]);
            $this->currentRound--;
        }
    }

    public function getRoundIndex()
    {
        return $this->currentRound;
//        return array_search($round, $this->rounds, true);
    }

    /**
     * @throws \Exception
     */
    public function commit()
    {
        $this->getCurrentRound()->commit();
        $this->closeRound();
    }

    /**
     * Reads all values of $entities and place it in lastKnownValues.
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
     * Returns last known values by the database. Those values are currently known by the database and
     * should be used to query the database. Important for event hooks, since objects in preSave for example
     * can contain changed primary keys which are not yet stored in the database and thus all queries
     * using this primary key return invalid results.
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
            throw new \InvalidARgumentException('Given id does not exists in known values pool. Create a snapshot(), use $orCurrent=true or use hasKnownValues().');
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
            $this->getConfiguration()->debug('retrieve firstLevelCache ' . $hashCode);
            return $this->firstLevelCache[$prefix][$hashCode];
        } else {
            $this->getConfiguration()->debug('rejected firstLevelCache ' . $hashCode);
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

        if (!isset($this->firstLevelCache[$prefix])){
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