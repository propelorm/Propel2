<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

/**
 * Connection wrapper class with debug enabled by default.
 *
 * Class kept for BC sake.
 */
class DebugPDO extends ConnectionWrapper
{
    /**
     * @var bool
     */
    protected $useDebugModeOnInstance = true;
}
