<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Sql\Sqlite;

use Propel\Generator\Builder\Sql\DataSQLBuilder;

/**
 * SQLite class for building data dump SQL.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class SqliteDataSQLBuilder extends DataSQLBuilder
{
    /**
     * @param  mixed  $blob
     * @return string
     */
    protected function getBlobSql($blob)
    {
        if (is_resource($blob)) {
            return fopen($blob, 'rb');
        }

        return (string) $blob;
    }
}
