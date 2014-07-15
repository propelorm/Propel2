<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Exception;

class JsonParseException extends RuntimeException implements ExceptionInterface
{
    /**
     * Create an exception based on error codes returned by json_last_error function
     *
     * @param int $error A JSON error constant, as returned by json_last_error()
     * @see http://www.php.net/manual/en/function.json-last-error.php
     */
    public function __construct($error)
    {
        $message = 'Error while parsing Json configuration file: ';

        if (!function_exists('json_last_error_msg')) {
            switch ($error) {
                case JSON_ERROR_DEPTH:
                    $message .= 'maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $message .= 'underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $message .= 'unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $message .= 'syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $message .= 'malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $message .= 'unknown error';
                    break;
            }
        } else {
            $message .= json_last_error_msg();
        }

        parent::__construct($message);
    }
}
