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
 * A validator for required fields.
 *
 * Below is an example usage for your Propel xml schema file.
 *
 * <code>
 *   <column name="username" type="VARCHAR" size="25" required="true" />
 *
 *   <validator column="username">
 *     <rule name="required" message="Username is required." />
 *   </validator>
 * </code>
 *
 * @author     Michael Aichler <aichler@mediacluster.de>
 */
class RequiredValidator implements BasicValidator
{
    /**
     * @see       BasicValidator::isValid()
     *
     * @param     ValidatorMap  $map
     * @param     string        $str
     *
     * @return    Boolean
     */
    public function isValid (ValidatorMap $map, $str)
    {
        return !empty($str);
    }
}
