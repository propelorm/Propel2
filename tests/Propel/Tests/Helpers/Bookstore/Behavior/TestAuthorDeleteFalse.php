<?php

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Runtime\Connection\ConnectionInterface;

class TestAuthorDeleteFalse extends TestAuthor
{
    public function preDelete(ConnectionInterface $con = null)
    {
        parent::preDelete($con);
        $this->setFirstName('Pre-Deleted');

        return false;
    }
}
