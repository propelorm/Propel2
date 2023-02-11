<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Helpers;

use Propel\Runtime\Propel;

trait CheckMysql8Trait
{
    /**
     * @var bool|null
     */
    protected static $isAtLeastMysql8 = null;

    /**
     * @param string|null $connectionName
     *
     * @return bool
     */
    protected function checkMysqlVersionAtLeast8(?string $connectionName = null): bool
    {
        if (static::$isAtLeastMysql8 === null) {
            static::$isAtLeastMysql8 = $this->queryMySqlVersionAtLeast8($connectionName);
        }

        return static::$isAtLeastMysql8;
    }

    /**
     * @param string|null $connectionName
     *
     * @return bool
     */
    protected function queryMySqlVersionAtLeast8(?string $connectionName = null): bool
    {
        $con = Propel::getServiceContainer()->getConnection($connectionName);
        $query = "SELECT VERSION() NOT LIKE '%MariaDB%' AND VERSION() >= 8";
        $result = $con->query($query)->fetchColumn(0);

        return (bool)$result;
    }
}
