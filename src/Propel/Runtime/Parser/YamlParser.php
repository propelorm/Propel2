<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Parser;

use Symfony\Component\Yaml\Yaml;

/**
 * YAML parser. Converts data between associative array and YAML formats
 *
 * @author Francois Zaninotto
 */
class YamlParser extends AbstractParser
{
    /**
     * Converts data from an associative array to YAML.
     *
     * @param  array  $array Source data to convert
     * @return string Converted data, as a YAML string
     */
    public function fromArray($array)
    {
        return Yaml::dump($array, 3);
    }

    /**
     * Alias for YamlParser::fromArray()
     *
     * @param  array  $array Source data to convert
     * @return string Converted data, as a YAML string
     */
    public function toYAML($array)
    {
        return $this->fromArray($array);
    }

    /**
     * Converts data from YAML to an associative array.
     *
     * @param  string $data Source data to convert, as a YAML string
     * @return array  Converted data
     */
    public function toArray($data)
    {
        return Yaml::parse($data);
    }

    /**
     * Alias for YamlParser::toArray()
     *
     * @param  string $data Source data to convert, as a YAML string
     * @return array  Converted data
     */
    public function fromYAML($data)
    {
        return $this->toArray($data);
    }
}
