<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * statement formatter for Propel query
 * format() returns a PDO statement
 *
 * @author Francois Zaninotto
 */
class StatementFormatter extends AbstractFormatter
{
    public function format(StatementInterface $stmt)
    {
        return $stmt;
    }

    public function formatOne(StatementInterface $stmt)
    {
        return $stmt->rowCount() > 0 ? $stmt : null;
    }

    public function formatRecord($record = null)
    {
        throw new PropelException('The Statement formatter cannot transform a record into a statement');
    }

    public function isObjectFormatter()
    {
        return false;
    }
}
