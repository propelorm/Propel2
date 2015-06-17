<?php

namespace Propel\Runtime\Event;

use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Session\Session;

class SaveEvent extends RepositoryEvent
{
    /**
     * @var object[]
     */
    protected $entitiesToInsert;
    protected $entitiesToUpdate;

    /**
     * @param Session   $session
     * @param EntityMap $entityMap
     * @param object[]  $entitiesToInsert
     * @param object[]  $entitiesToUpdate
     */
    public function __construct(Session $session, EntityMap $entityMap, $entitiesToInsert = [], $entitiesToUpdate = [])
    {
        parent::__construct($session, $entityMap);
        $this->entitiesToInsert = $entitiesToInsert;
        $this->entitiesToUpdate = $entitiesToUpdate;
    }

    /**
     * Returns both, entities to be inserted and updated.
     *
     * @return object[]
     */
    public function getEntities()
    {
        return array_merge($this->entitiesToInsert, $this->entitiesToUpdate);
    }

    /**
     * @return object[]
     */
    public function getEntitiesToInsert()
    {
        return $this->entitiesToInsert;
    }

    /**
     * @return object[]
     */
    public function getEntitiesToUpdate()
    {
        return $this->entitiesToUpdate;
    }

    /**
     * @param mixed $entitiesToUpdate
     */
    public function setEntitiesToUpdate($entitiesToUpdate)
    {
        $this->entitiesToUpdate = $entitiesToUpdate;
    }

    /**
     * @param \object[] $entitiesToInsert
     */
    public function setEntitiesToInsert($entitiesToInsert)
    {
        $this->entitiesToInsert = $entitiesToInsert;
    }
}