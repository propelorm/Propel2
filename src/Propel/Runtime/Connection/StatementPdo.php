<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Connection\StatementInterface;

/**
 * PDO statement that provides the basic enhancements that are required by Propel.
 */
class StatementPdo extends \PDOStatement implements StatementInterface
{
    protected function __construct()
    {
    }
}