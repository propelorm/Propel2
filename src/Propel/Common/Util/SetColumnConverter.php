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
     * @param array<int, string> $valueSet
     *
     * @throws \Propel\Common\Exception\SetColumnConverterException
     *
     * @return string Integer value as string.
     */
    public static function convertToInt($val, array $valueSet): string
    {
        if ($val === null) {
            return '0';
        }
        if (!is_array($val)) {
            $val = [$val];
        }
        $bitValue = str_repeat('0', count($valueSet));
        foreach ($val as $value) {
            $index = array_search($value, $valueSet);
            if ($index === false) {
                throw new SetColumnConverterException(sprintf('Value "%s" is not among the valueSet', $value), $value);
            }
            $bitValue[$index] = '1';
        }

        return base_convert(strrev($bitValue), 2, 10);
    }

    /**
     * Converts set column integer value to corresponding array.
     *
     * @param string|null $val
     * @param array<string> $valueSet
     *
     * @throws \Propel\Common\Exception\SetColumnConverterException
     *
     * @return array<string>
     */
    public static function convertIntToArray(?string $val, array $valueSet): array
    {
        if ($val === null) {
            return [];
        }
        $bitValueStr = strrev(base_convert($val, 10, 2));
        $valueArr = [];
        for ($bit = 0, $bitlen = strlen($bitValueStr); $bit < $bitlen; $bit++) {
            if (!isset($valueSet[$bit])) {
                throw new SetColumnConverterException(sprintf('Unknown value key: "%s"', $bit), $bit);
            }
            if ($bitValueStr[$bit] === '1') {
                $valueArr[] = $valueSet[$bit];
            }
        }

        return $valueArr;
    }
}
