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
    public function __construct(Session $session, EntityMap $entityMap, $entitiesToInsert, $entitiesToUpdate)
    {
        $this->session = $session;
        $this->entityMap = $entityMap;
        $this->entitiesToInsert = $entitiesToInsert;
        $this->entitiesToUpdate = $entitiesToUpdate;
    }

    /**
     * @return \object[]
     */
    public function getEntitiesToInsert()
    {
        return $this->entitiesToInsert;
    }

    /**
     * @return mixed
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