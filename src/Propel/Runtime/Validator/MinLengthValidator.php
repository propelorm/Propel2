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
 * A validator for minimum string length.
 *
 * <code>
 *   <column name="password" type="VARCHAR" size="34" required="true" />
 *
 *   <validator column="password">
 *     <rule name="minLength" value="5" message="Passwort must be at least ${value} characters !" />
 *   </validator>
 * </code>
 *
 * @author     Michael Aichler <aichler@mediacluster.de>
 * @version    $Revision$
 * @package    propel.runtime.validator
 */
class MinLengthValidator implements BasicValidator
{
    /**
     * @see       BasicValidator::isValid()
     *
     * @param     ValidatorMap  $map
     * @param     string        $str
     *
     * @return    boolean
     */
    public function isValid(ValidatorMap $map, $str)
    {
        $len = function_exists('mb_strlen') ? mb_strlen($str) : strlen($str);
        return $len >= intval($map->getValue());
    }
}
