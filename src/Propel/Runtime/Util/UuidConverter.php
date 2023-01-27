<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Util;

/**
 * Helps to manually convert UUIDs to byte types
 */
class UuidConverter
{
    /**
     * Transforms a UUID string to a binary string.
     *
     * @param string $uuid
     * @param bool $swapFlag Swap first four bytes for better indexing of version-1 UUIDs (@link https://dev.mysql.com/doc/refman/8.0/en/miscellaneous-functions.html#function_uuid-to-bin)
     *
     * @return string
     */
    public static function uuidToBin(string $uuid, bool $swapFlag = true): string
    {
        $rawHex = (!$swapFlag)
            ? str_replace('-', '', $uuid)
            : preg_replace(
                '/([^-]+)-([^-]+)-([^-]+)-([^-]+)-(.*)/',
                '$3$2$1$4$5',
                $uuid,
            );

        return hex2bin((string)$rawHex) ?: '';
    }

    /**
     * Transforms a binary string to a UUID string.
     *
     * @param string $bin
     * @param bool $swapFlag Assume bytes were swapped (@link https://dev.mysql.com/doc/refman/8.0/en/miscellaneous-functions.html#function_bin-to-uuid)
     *
     * @return string
     */
    public static function binToUuid(string $bin, bool $swapFlag = true): string
    {
        $rawHex = bin2hex($bin);
        $recombineFormat = $swapFlag ? '$3$4-$2-$1-$5-$6' : '$1$2-$3-$4-$5-$6';

        return (string)preg_replace(
            '/(\w{4})(\w{4})(\w{4})(\w{4})(\w{4})(\w{12})/',
            $recombineFormat,
            $rawHex,
        );
    }

    /**
     * @param array|string|null $uuid
     * @param bool $swapFlag
     *
     * @return array|string|null
     */
    public static function uuidToBinRecursive($uuid, bool $swapFlag = true)
    {
        if (!$uuid) {
            return $uuid;
        }
        if (is_string($uuid)) {
            return self::uuidToBin($uuid, $swapFlag);
        }

        return array_map(fn ($uuidItem) => self::uuidToBinRecursive($uuidItem, $swapFlag), $uuid);
    }

    /**
     * @param array|string|null $bin
     * @param bool $swapFlag
     *
     * @return array|string|null
     */
    public static function binToUuidRecursive($bin, bool $swapFlag = true)
    {
        if (!$bin) {
            return $bin;
        }
        if (is_string($bin)) {
            return self::binToUuid($bin, $swapFlag);
        }

        return array_map(fn ($binItem) => self::binToUuidRecursive($binItem, $swapFlag), $bin);
    }
}
