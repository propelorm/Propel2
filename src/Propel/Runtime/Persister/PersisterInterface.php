<?php

namespace Propel\Runtime\Persister;

interface PersisterInterface
{
    public function persist($entities);
    public function remove($entities);
}