<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Exception;

use Exception;
use InvalidArgumentException;

/**
 * Exception for Propel\Common\Util\SetColumnConverter class.
 *
 * @author Moritz Schroeder <moritz.schroeder@molabs.de>
 */
class SetColumnConverterException extends InvalidArgumentException
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param string $message
     * @param mixed $value
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message, $value, $code = 0, ?Exception $previous = null)
    {
        $this->value = $value;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns param "value".
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
