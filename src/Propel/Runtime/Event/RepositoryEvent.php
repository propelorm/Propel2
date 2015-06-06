<?php

namespace Propel\Runtime\Event;

use Propel\Runtime\Configuration;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Session\Session;
use Symfony\Component\EventDispatcher\Event;

class RepositoryEvent extends Event
{
    /**
     * @var EntityMap
     */
    protected $entityMap;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session   $session
     * @param EntityMap $entityMap
     */
    public function __construct(Session $session, EntityMap $entityMap)
    {
        $this->session = $session;
        $this->entityMap = $entityMap;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return EntityMap
     */
    public function getEntityMap()
    {
        return $this->entityMap;
    }
}