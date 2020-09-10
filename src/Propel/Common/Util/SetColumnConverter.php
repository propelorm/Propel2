<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Util;

use Propel\Common\Exception\SetColumnConverterException;

/**
 * Class converts SET column values between integer and string/array representation.
 *
 * @author Moritz Schroeder <moritz.schroeder@molabs.de>
 */
class SetColumnConverter
{
    /**
     * Converts set column values to the corresponding integer.
     *
     * @param mixed $val
     * @param array $valueSet
     *
     * @throws \Propel\Common\Exception\SetColumnConverterException
     *
     * @return string|int
     */
    public static function convertToInt($val, array $valueSet)
    {
        if ($val === null) {
            return 0;
        }
        if (!is_array($val)) {
            $val = [$val];
        }
        $bitValueArr = array_pad([], count($valueSet), '0');
        foreach ($val as $value) {
            if (!in_array($value, $valueSet)) {
                throw new SetColumnConverterException(sprintf('Value "%s" is not among the valueSet', $value), $value);
            }
            $bitValueArr[array_search($value, $valueSet)] = '1';
        }

        return base_convert(implode('', array_reverse($bitValueArr)), 2, 10);
    }

    /**
     * Converts set column integer value to corresponding array.
     *
     * @param mixed $val
     * @param array $valueSet
     *
     * @throws \Propel\Common\Exception\SetColumnConverterException
     *
     * @return array
     */
    public static function convertIntToArray($val, array $valueSet)
    {
        if ($val === null) {
            return [];
        }
        $bitValueArr = array_reverse(str_split(base_convert($val, 10, 2)));
        $valueArr = [];
        foreach ($bitValueArr as $bit => $bitValue) {
            if (!isset($valueSet[$bit])) {
                throw new SetColumnConverterException(sprintf('Unknown value key: "%s"', $bit), $bit);
            }
            if ($bitValue === '1') {
                $valueArr[] = $valueSet[$bit];
            }
        }

        return $valueArr;
    }
}
