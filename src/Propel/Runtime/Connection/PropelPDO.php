<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Connection\ConnectionWrapper;

/**
 * Class kept for BC sake - the functionality of the old PropelPDO class was moved to:
 * - ConnectionWrapper for the nested transactions, and logging
 * - PDOConnection for the PDO wrapper
 */
class PropelPDO extends ConnectionWrapper
{
}
