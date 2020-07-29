<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter\MSSQL;

/**
 * dblib doesn't support transactions so we need to add a workaround for transactions, last insert ID, and quoting
 */
class MssqlDebugPDO extends MssqlPropelPDO
{
    /**
     * @var bool
     */
    public $useDebug = true;
}
