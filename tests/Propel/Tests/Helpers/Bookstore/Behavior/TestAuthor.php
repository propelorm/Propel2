<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Tests\Bookstore\Author;

class TestAuthor extends Author
{
    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return bool
     */
    public function preInsert(?ConnectionInterface $con = null): bool
    {
        parent::preInsert($con);
        $this->setFirstName('PreInsertedFirstname');

        return true;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return void
     */
    public function postInsert(?ConnectionInterface $con = null): void
    {
        parent::postInsert($con);
        $this->setLastName('PostInsertedLastName');
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return bool
     */
    public function preUpdate(?ConnectionInterface $con = null): bool
    {
        parent::preUpdate($con);
        $this->setFirstName('PreUpdatedFirstname');

        return true;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return void
     */
    public function postUpdate(?ConnectionInterface $con = null): void
    {
        parent::postUpdate($con);
        $this->setLastName('PostUpdatedLastName');
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return bool
     */
    public function preSave(?ConnectionInterface $con = null): bool
    {
        parent::preSave($con);
        $this->setEmail('pre@save.com');

        return true;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return void
     */
    public function postSave(?ConnectionInterface $con = null): void
    {
        parent::postSave($con);
        $this->setAge(115);
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return int|null
     */
    public function preDelete(?ConnectionInterface $con = null): bool
    {
        parent::preDelete($con);
        $this->setFirstName('Pre-Deleted');

        return true;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return void
     */
    public function postDelete(?ConnectionInterface $con = null): void
    {
        parent::postDelete($con);
        $this->setLastName('Post-Deleted');
    }
}
