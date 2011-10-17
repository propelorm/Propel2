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
 * A validator for regular expressions.
 *
 * This validator will return true, when the passed value does *not* match
 * the regular expression.
 *
 * If you do want to test if the value *matches* an expression, you can use
 * the MatchValidator class instead.
 *
 * Below is an example usage for your Propel xml schema file.
 *
 * <code>
 *   <column name="ISBN" type="VARCHAR" size="20" required="true" />
 *   <validator column="username">
 *     <!-- disallow everything that's not a digit or minus -->
 *     <rule
 *       name="notMatch"
 *       value="/[^\d-]+/"
 *       message="Please enter a valid email adress." />
 *   </validator>
 * </code>
 *
 * @author     Michael Aichler <aichler@mediacluster.de>
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.runtime.validator
 */
class NotMatchValidator implements BasicValidator
{
    /**
     * Prepares the regular expression entered in the XML
     * for use with preg_match().
     *
     * @param      string  $exp
     * @return     string
     */
    private function prepareRegexp($exp)
    {
        // remove surrounding '/' marks so that they don't get escaped in next step
        if ($exp{0} !== '/' || $exp{strlen($exp)-1} !== '/' ) {
            $exp = '/' . $exp . '/';
        }

        // if they did not escape / chars; we do that for them
        $exp = preg_replace('/([^\\\])\/([^$])/', '$1\/$2', $exp);

        return $exp;
    }

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
        return (preg_match($this->prepareRegexp($map->getValue()), $str) == 0);
    }
}
