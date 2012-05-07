<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Validator;

use Propel\Runtime\Map\ValidatorMap;

/**
 * A validator for validating the (PHP) type of the value submitted.
 *
 * <code>
 *   <column name="some_int" type="INTEGER" required="true"/>
 *
 *   <validator column="some_int">
 *     <rule name="type" value="integer" message="Please specify an integer value for some_int column." />
 *   </validator>
 * </code>
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class TypeValidator implements BasicValidator
{
    /**
     * @see       BasicValidator::isValid()
     *
     * @param     ValidatorMap  $map
     * @param     mixed         $str
     *
     * @return    Boolean
     */
    public function isValid(ValidatorMap $map, $str)
    {
        switch ($map->getValue()) {
            case 'array':
                return is_array($str);
                break;
            case 'bool':
            case 'boolean':
                return is_bool($str);
                break;
            case 'float':
                return is_float($str);
                break;
            case 'int':
            case 'integer':
                return is_int($str);
                break;
            case 'numeric':
                return is_numeric($str);
                break;
            case 'object':
                return is_object($str);
                break;
            case 'resource':
                return is_resource($str);
                break;
            case 'scalar':
                return is_scalar($str);
                break;
            case 'string':
                return is_string($str);
                break;
            case 'function':
                return function_exists($str);
                break;
            default:
                throw new PropelException('Unkonwn type ' . $map->getValue());
                break;
        }
    }
}
