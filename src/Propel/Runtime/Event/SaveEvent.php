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
    protected $entities;

    /**
     * @param Session   $session
     * @param EntityMap $entityMap
     * @param object[]  $entities
     */
    public function __construct(Session $session, EntityMap $entityMap, $entities = [])
    {
        parent::__construct($session, $entityMap);
        $this->entities = $entities;
    }

    /**
     * Returns both, entities to be inserted and updated.
     *
     * @return object[]
     */
    public function getEntities()
    {
        return $this->entities;
    }
}