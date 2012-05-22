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
 * Connection wrapper class with debug enabled by default.
 *
 * Class kept for BC sake.
 */
class DebugPDO extends ConnectionWrapper
{
    public $useDebug = true;
}
