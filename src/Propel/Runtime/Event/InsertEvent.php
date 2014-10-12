<?php

namespace Propel\Runtime\Event;

use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Session\Session;

class InsertEvent extends RepositoryEvent
{
    /**
     * @var object[]
     */
    protected $entities;

    /**
     * @param Session   $session
     * @param EntityMap $entityMap
     * @param object[]  $entities
     */
    public function __construct(Session $session, EntityMap $entityMap, $entities)
    {
        $this->session = $session;
        $this->entityMap = $entityMap;
        $this->entities = $entities;
    }

    /**
     * @return \object[]
     */
    public function getEntities()
    {
        return $this->entities;
    }
}