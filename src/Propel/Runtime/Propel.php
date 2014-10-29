<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Psr\Log\LoggerInterface;

/**
 * Propel class.
 */
class Propel
{
    /**
     * The Propel version.
     */
    const VERSION = '2.0.0-dev';
}
