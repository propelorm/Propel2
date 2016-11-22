<?php

namespace Propel\Runtime\Persister;

interface PersisterInterface
{
    public function commit($entities);
    public function remove($entities);
}