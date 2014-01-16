<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config;

use Propel\Generator\Config\XmlToArrayConverter as BaseConverter;
use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\XmlParseException;

/**
 * Class to convert xml to array with a more functional error handling
 *
 * @author Cristiano Cinotti
 */
class XmlToArrayConverter extends BaseConverter
{
    /**
     * Create a PHP array from the XML file
     *
     * @param String $xmlFile The XML file or a string containing xml to parse
     *
     * @return Array
     *
     * @throws Propel\Common\Config\Exception\XmlParseException if parse errors occur
     */
    public static function convert($xmlToParse)
    {
        if (!is_string($xmlToParse)) {
            throw new InvalidArgumentException("XmlToArrayConverter::convert method expects an xml file to parse, or a string containing valid xml");
        }

        if (file_exists($xmlToParse)) {
            $xmlToParse = file_get_contents($xmlToParse);
        }

        if (false === $xmlToParse) {
            throw new InvalidArgumentException('Error while reading configuration file');
        }

        //Empty xml file returns empty array
        if ('' === $xmlToParse) {
            return array();
        }

        if ($xmlToParse[0] !== '<') {
            throw new InvalidArgumentException('Invalid xml content');
        }

        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlToParse);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors();

        if (count($errors) > 0) {
            throw new XmlParseException($errors);
        }

        $conf = static::simpleXmlToArray($xml);

        return $conf;
    }
}
