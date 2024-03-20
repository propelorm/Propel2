<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Runtime\Connection\ConnectionInterface;

class TestAuthorDeleteFalse extends TestAuthor
{
    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return int|null
     */
    public function preDelete(?ConnectionInterface $con = null): bool
    {
        parent::preDelete($con);
        $this->setFirstName('Pre-Deleted');

        return false;
    }
}
