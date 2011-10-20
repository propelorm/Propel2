<?php

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Runtime\Connection\PropelPDO;

class TestAuthorSaveFalse extends TestAuthor
{
    public function preSave(PropelPDO $con = null)
    {
        parent::preSave($con);
        $this->setEmail("pre@save.com");

        return false;
    }
}
