<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Exception;

/**
 * This is the default exception which gets thrown when using the `requireOne` method. It indicates
 * that a model which is required to processed in the application flow could not be found by the
 * ModelCriteria.
 *
 * You can catch this exception in your applications front-controller to display a generic not-found
 * error message.
 */
class EntityNotFoundException extends RuntimeException
{
}
