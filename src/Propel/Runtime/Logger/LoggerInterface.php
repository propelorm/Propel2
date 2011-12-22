<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Logger;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
interface LoggerInterface
{
    /**
     */
    function emerg($message, array $context = array());

    /**
     */
    function alert($message, array $context = array());

    /**
     */
    function crit($message, array $context = array());

    /**
     */
    function err($message, array $context = array());

    /**
     */
    function warn($message, array $context = array());

    /**
     */
    function notice($message, array $context = array());

    /**
     */
    function info($message, array $context = array());

    /**
     */
    function debug($message, array $context = array());
}
