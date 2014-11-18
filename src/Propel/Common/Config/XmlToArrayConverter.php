<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config;

use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\XmlParseException;

/**
 * Class to convert an xml string to array
 */
class XmlToArrayConverter
{
    /**
     * Create a PHP array from the XML file
     *
     * @param String $xmlFile The XML file or a string containing xml to parse
     *
     * @return Array
     *
     * @throws \Propel\Common\Config\Exception\XmlParseException if parse errors occur
     */
    public static function convert($xmlToParse)
    {
        if (!is_string($xmlToParse)) {
            throw new InvalidArgumentException("XmlToArrayConverter::convert method expects an xml file to parse, or a string containing valid xml");
        }

        if (file_exists($xmlToParse)) {
            $xmlToParse = file_get_contents($xmlToParse);
        }

        //Empty xml file returns empty array
        if ('' === $xmlToParse) {
            return array();
        }

        if ($xmlToParse[0] !== '<') {
            throw new InvalidArgumentException('Invalid xml content');
        }

        $currentEntityLoader = libxml_disable_entity_loader(true);
        $currentInternalErrors = libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlToParse);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($currentInternalErrors);
        libxml_disable_entity_loader($currentEntityLoader);

        if (count($errors) > 0) {
            throw new XmlParseException($errors);
        }

        $conf = self::simpleXmlToArray($xml);

        return $conf;
    }

    /**
     * Recursive function that converts an SimpleXML object into an array.
     * @author     Christophe VG (based on code form php.net manual comment)
     *
     * @param  \SimpleXMLElement $xml SimpleXML object.
     * @return array             Array representation of SimpleXML object.
     */
    protected static function simpleXmlToArray($xml)
    {
        $ar = array();
        foreach ($xml->children() as $k => $v) {
            // recurse the child
            $child = self::simpleXmlToArray($v);

            // if it's not an array, then it was empty, thus a value/string
            if (count($child) == 0) {
                $child = self::getConvertedXmlValue($v);
            }

            // add the children attributes as if they where children
            foreach ($v->attributes() as $ak => $av) {
                if ($ak == 'id') {
                    // special exception: if there is a key named 'id'
                    // then we will name the current key after that id
                    $k = self::getConvertedXmlValue($av);
                } else {
                    // otherwise, just add the attribute like a child element
                    $child[$ak] = self::getConvertedXmlValue($av);
                }
            }

            // if the $k is already in our children list, we need to transform
            // it into an array, else we add it as a value
            if (!in_array($k, array_keys($ar))) {
                $ar[$k] = $child;
            } else {
                // (This only applies to nested nodes that do not have an @id attribute)

                // if the $ar[$k] element is not already an array, then we need to make it one.
                // this is a bit of a hack, but here we check to also make sure that if it is an
                // array, that it has numeric keys.  this distinguishes it from simply having other
                // nested element data.
                if (!is_array($ar[$k]) || !isset($ar[$k][0])) {
                    $ar[$k] = array($ar[$k]);
                }

                $ar[$k][] = $child;
            }
        }

        return $ar;
    }

    /**
     * Process XML value, handling boolean, if appropriate.
     * @param  \SimpleXMLElement $value The simplexml value object.
     * @return mixed             string or boolean value
     */
    private static function getConvertedXmlValue($value)
    {
        $value = (string) $value; // convert from simplexml to string

        //handle numeric values
        if (is_numeric($value)) {
            if (ctype_digit($value)) {
                $value = intval($value);
            } else {
                $value = floatval($value);
            }
        }

        // handle booleans specially
        $lwr = strtolower($value);
        if ($lwr === "false") {
            return false;
        }
        if ($lwr === "true") {
            return true;
        }

        return $value;
    }
}
