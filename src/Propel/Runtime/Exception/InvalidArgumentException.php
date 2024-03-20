<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Exception;

use InvalidArgumentException as CoreInvalidArgumentException;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class InvalidArgumentException extends CoreInvalidArgumentException implements ExceptionInterface
{
}
