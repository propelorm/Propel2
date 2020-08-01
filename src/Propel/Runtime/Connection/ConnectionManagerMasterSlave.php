<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

/**
 * Manager for master/slave connection to a datasource.
 *
 * @deprecated Use ConnectionManagerPrimaryReplica instead.
 */
class ConnectionManagerMasterSlave extends ConnectionManagerPrimaryReplica
{
    /**
     * For replication, whether to always force the use of a master connection.
     *
     * @deprecated Use isForcePrimaryConnection() instead.
     *
     * @return bool
     */
    public function isForceMasterConnection()
    {
        return $this->isForcePrimaryConnection();
    }

    /**
     * For replication, set whether to always force the use of a master connection.
     *
     * @deprecated Use setForcePrimaryConnection() instead.
     *
     * @param bool $isForceMasterConnection
     *
     * @return void
     */
    public function setForceMasterConnection($isForceMasterConnection)
    {
        $this->setForcePrimaryConnection($isForceMasterConnection);
    }
}
