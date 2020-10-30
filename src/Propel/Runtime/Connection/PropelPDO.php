<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

/**
 * Class kept for BC sake - the functionality of the old PropelPDO class was moved to:
 * - ConnectionWrapper for the nested transactions, and logging
 * - PDOConnection for the PDO wrapper
 */
class PropelPDO extends ConnectionWrapper
{
}
