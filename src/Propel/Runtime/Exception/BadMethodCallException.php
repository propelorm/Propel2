<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Exception;

use BadMethodCallException as CoreBadMethodCallException;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class BadMethodCallException extends CoreBadMethodCallException implements ExceptionInterface
{
}
