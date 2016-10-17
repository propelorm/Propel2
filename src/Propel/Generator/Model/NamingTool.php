<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

class NamingTool
{
    /**
     * Convert a string from underscore to camel case.
     * E.g. my_own_variable => myOwnVariable
     *
     * @param string $string The string to convert
     * @static
     *
     * @return string
     */
    public static function toCamelCase($string)
    {
        if (strtoupper($string) === $string) {
            return $string;
        }

        return lcfirst(implode('', array_map('ucfirst', explode('_', $string))));
    }

    /**
     * Convert a string from camel case to underscore.
     * E.g. myOwnVariable => my_own_variable.
     *
     * Numbers are considered as part of its previous piece:
     * E.g. myTest3Variable => my_test3_variable
     * Other use cases can be found in Propel\Tests\Generator\Model\NamingToolTest class.
     *
     * @param string $string The string to convert
     * @static
     *
     * @return string
     */
    public static function toUnderscore($string)
    {
        $out = trim(strtolower(preg_replace('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', '$1_$2', $string)), '_');

        return str_replace('__', '_', $out);
    }

    /**
     * Convert a string from underscore to camel case, with upper-case first letter.
     * This function is useful while writing getter and setter method names.
     * E.g. my_own_variable => MyOwnVariable
     *
     * @param string $string
     * @static
     *
     * @return string
     */
    public static function toUpperCamelCase($string)
    {
        return implode('', array_map('ucfirst', explode('_', $string)));
    }
}
