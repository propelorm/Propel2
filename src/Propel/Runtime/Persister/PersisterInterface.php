<?php

namespace Propel\Runtime\Persister;

use Propel\Runtime\Map\EntityMap;

interface PersisterInterface
{
    public function commit(EntityMap $entityMap, $entities);
    public function remove(EntityMap $entityMap, $entities);

    /**
     * Called when \Propel\Runtime\Session\Session::commit is finished (with all its rounds)
     */
    public function sessionCommitEnd();
}