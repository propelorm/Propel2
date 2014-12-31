<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
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
     * @param  array  $array Source data to convert
     * @param string $rootKey
     * @return string Converted data, as a JSON string
     */
    public function fromArray($array, $rootKey = null)
    {
        $this->convertDateTimesToString($array);
        return json_encode($rootKey === null ? $array : [$rootKey => $array]);
    }

    /**
     * PHP's `json_encode` will transform `\DateTime` instances to a custom
     * encoded format, e.g. `{"date":"2014-10-01 15:32:26","timezone_type":3,"timezone":"Europe\/Rome"}`
     * which is hard to parse with javascript and other languages.
     *
     * To make parsing the JSON easier we will avoid this and format all `\DateTime` instances
     * as ISO 8601 strings before passing the array to `json_encode`.
     *
     * @param $array
     */
    private function convertDateTimesToString(&$array)
    {
        array_walk_recursive($array, function(&$value) {
            if ($value instanceof \DateTime) {
                $value->setTimezone(new \DateTimeZone('UTC'));
                $value = $value->format('Y-m-d\TH:i:s\Z');
            }
        });
    }

    /**
     * Alias for JsonParser::fromArray()
     *
     * @param  array $array Source data to convert
     * @param string $rootKey
     * @return string Converted data, as a JSON string
     */
    public function toJSON($array, $rootKey = null)
    {
        return $this->fromArray($array, $rootKey);
    }

    /**
     * Converts data from JSON to an associative array.
     *
     * @param  string $data Source data to convert, as a JSON string
     * @param string $rootKey
     * @return array  Converted data
     */
    public function toArray($data, $rootKey = null)
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
     * @param  string $data Source data to convert, as a JSON string
     * @param string $rootKey
     * @return array  Converted data
     */
    public function fromJSON($data, $rootKey = null)
    {
        return $this->toArray($data, $rootKey);
    }
}
