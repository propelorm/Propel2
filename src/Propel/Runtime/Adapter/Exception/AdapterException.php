<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Adapter\Exception;

use Propel\Runtime\Exception\RuntimeException;

use \Exception;

class AdapterException extends RuntimeException
{
    public function __construct($message, Exception $exception)
    {
        parent::__construct($message, 0, $exception);
    }
}
