<?php

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Runtime\Connection\ConnectionInterface;

class TestAuthorSaveFalse extends TestAuthor
{
    public function preSave(ConnectionInterface $con = null)
    {
        parent::preSave($con);
        $this->setEmail('pre@save.com');

        return false;
    }
}
