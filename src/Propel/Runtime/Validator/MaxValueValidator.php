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
 * A validator for maximum values.
 *
 * Below is an example usage for your Propel xml schema file.
 *
 * <code>
 *   <column name="articles" type="INTEGER" required="true" />
 *
 *   <validator column="articles">
 *     <rule name="minValue" value="1"  message="Minimum value for selected articles is ${value} !" />
 *     <rule name="maxValue" value="10"  message="Maximum value for selected articles is ${value} !" />
 *   </validator>
 * </code>
 *
 * @author     Michael Aichler <aichler@mediacluster.de>
 */
class MaxValueValidator implements BasicValidator
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
        if (null === $str || !is_numeric($str)) {
            return false;
        }

        return (int) $str <= (int) $map->getValue();
    }
}
