<?php

namespace Propel\Runtime\Event;

use Propel\Runtime\Configuration;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Session\Session;

class PersistEvent extends RepositoryEvent
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @param Session   $session
     * @param object    $entity
     */
    public function __construct(Session $session, $entity)
    {
        $this->session = $session;
        $this->entity = $entity;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}