<?php

namespace Propel\Runtime\Event;

use Propel\Runtime\Session\Session;
use Symfony\Component\EventDispatcher\Event;

class CommitEvent extends Event
{

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->ssession = $session;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }
}