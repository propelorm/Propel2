<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Parser;

/**
 * JSON parser. Converts data between associative array and JSON formats
 *
 * @author Francois Zaninotto
 */
class JsonParser extends AbstractParser
{
    /**
     * Converts data from an associative array to JSON.
     *
     * @param array $array Source data to convert
     * @param string|null $rootKey
     *
     * @return string Converted data, as a JSON string
     */
    public function fromArray(array $array, ?string $rootKey = null): string
    {
        return json_encode($rootKey === null ? $array : [$rootKey => $array], JSON_THROW_ON_ERROR);
    }

    /**
     * Alias for JsonParser::fromArray()
     *
     * @param array $array Source data to convert
     * @param string|null $rootKey
     *
     * @return string Converted data, as a JSON string
     */
    public function toJSON(array $array, ?string $rootKey = null): string
    {
        return $this->fromArray($array, $rootKey);
    }

    /**
     * Converts data from JSON to an associative array.
     *
     * @param string $data Source data to convert, as a JSON string
     * @param string|null $rootKey
     *
     * @return array Converted data
     */
    public function toArray(string $data, ?string $rootKey = null): array
    {
        $data = json_decode($data, true);

        if ($rootKey === null) {
            return $data;
        }

        if (!isset($data[$rootKey])) {
            return [];
        }

        return $data[$rootKey];
    }

    /**
     * Alias for JsonParser::toArray()
     *
     * @param string $data Source data to convert, as a JSON string
     * @param string|null $rootKey
     *
     * @return array Converted data
     */
    public function fromJSON(string $data, ?string $rootKey = null): array
    {
        return $this->toArray($data, $rootKey);
    }
}
