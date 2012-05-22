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
     * Returns string processed by sqlite_udf_encode_binary() to ensure that
     * binary contents will be handled correctly by sqlite.
     *
     * @param  mixed  $blob
     * @return string
     */
    protected function getBlobSql($blob)
    {
        // they took magic __toString() out of PHP5.0.0; this sucks
        if (is_object($blob)) {
            $blob = $blob->__toString();
        }

        return sprintf("'%s'", sqlite_udf_encode_binary($blob));
    }
}
