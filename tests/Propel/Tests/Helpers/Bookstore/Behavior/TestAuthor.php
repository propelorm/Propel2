<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Tests\Bookstore\Author;

use Propel\Runtime\Connection\ConnectionInterface;

class TestAuthor extends Author
{
    public function preInsert(ConnectionInterface $con = null)
    {
        parent::preInsert($con);
        $this->setFirstName('PreInsertedFirstname');

        return true;
    }

    public function postInsert(ConnectionInterface $con = null)
    {
        parent::postInsert($con);
        $this->setLastName('PostInsertedLastName');
    }

    public function preUpdate(ConnectionInterface $con = null)
    {
        parent::preUpdate($con);
        $this->setFirstName('PreUpdatedFirstname');

        return true;
    }

    public function postUpdate(ConnectionInterface $con = null)
    {
        parent::postUpdate($con);
        $this->setLastName('PostUpdatedLastName');
    }

    public function preSave(ConnectionInterface $con = null)
    {
        parent::preSave($con);
        $this->setEmail("pre@save.com");

        return true;
    }

    public function postSave(ConnectionInterface $con = null)
    {
        parent::postSave($con);
        $this->setAge(115);
    }

    public function preDelete(ConnectionInterface $con = null)
    {
        parent::preDelete($con);
        $this->setFirstName("Pre-Deleted");

        return true;
    }

    public function postDelete(ConnectionInterface $con = null)
    {
        parent::postDelete($con);
        $this->setLastName("Post-Deleted");
    }
}
