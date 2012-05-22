<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\Connection\StatementInterface;
use PDOStatement as BasePdoStatement;

/**
 * PDO statement that provides the basic enhancements that are required by Propel.
 */
class PdoStatement extends BasePdoStatement implements StatementInterface
{
    protected function __construct()
    {
    }
}
