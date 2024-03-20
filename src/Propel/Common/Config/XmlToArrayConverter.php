<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config;

use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\XmlParseException;
use SimpleXMLElement;

/**
 * Class to convert an xml string to array
 */
class XmlToArrayConverter
{
    /**
     * Create a PHP array from the XML file
     *
     * @param string $xmlToParse The XML file or a string containing xml to parse
     *
     * @throws \Propel\Common\Config\Exception\XmlParseException if parse errors occur
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException
     *
     * @return array
     */
    public static function convert(string $xmlToParse): array
    {
        $isFile = file_exists($xmlToParse);

        if ($isFile) {
            $xmlPrefix = file_get_contents($xmlToParse, false, null, 0, 1);
        } else {
            $xmlPrefix = $xmlToParse[0] ?? '';
        }

        //Empty xml file returns empty array
        if ($xmlPrefix === '') {
            return [];
        }

        if ($xmlPrefix !== '<') {
            throw new InvalidArgumentException('Invalid xml content');
        }

        $currentInternalErrors = libxml_use_internal_errors(true);

        if ($isFile) {
            $xml = simplexml_load_file($xmlToParse);
            if ($xml instanceof SimpleXMLElement) {
                dom_import_simplexml($xml)->ownerDocument->xinclude();
            }
        } else {
            $xml = simplexml_load_string($xmlToParse);
        }

        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($currentInternalErrors);

        if ($xml === false || count($errors) > 0) {
            throw new XmlParseException($errors);
        }

        return static::simpleXmlToArray($xml);
    }

    /**
     * Recursive function that converts an SimpleXML object into an array.
     *
     * @author Christophe VG (based on code form php.net manual comment)
     *
     * @param \SimpleXMLElement $xml SimpleXML object.
     *
     * @return array Array representation of SimpleXML object.
     */
    protected static function simpleXmlToArray(SimpleXMLElement $xml): array
    {
        $ar = [];
        foreach ($xml->children() as $k => $v) {
            // recurse the child
            $child = static::simpleXmlToArray($v);

            // if it's not an array, then it was empty, thus a value/string
            if ($child === []) {
                $child = self::getConvertedXmlValue($v);
            }

            // add the children attributes as if they where children
            foreach ($v->attributes() as $ak => $av) {
                if ($ak === 'id') {
                    // special exception: if there is a key named 'id'
                    // then we will name the current key after that id
                    $k = (string)$av;
                    if (ctype_digit($k)) {
                        $k = (int)$k;
                    }
                } else {
                    // otherwise, just add the attribute like a child element
                    if (!is_array($child)) {
                        $child = [];
                    }

                    $child[$ak] = self::getConvertedXmlValue($av);
                }
            }

            // if the $k is already in our children list, we need to transform
            // it into an array, else we add it as a value
            if (!array_key_exists($k, $ar)) {
                $ar[$k] = $child;
            } else {
                // (This only applies to nested nodes that do not have an @id attribute)

                // if the $ar[$k] element is not already an array, then we need to make it one.
                // this is a bit of a hack, but here we check to also make sure that if it is an
                // array, that it has numeric keys.  this distinguishes it from simply having other
                // nested element data.
                if (!is_array($ar[$k]) || !isset($ar[$k][0])) {
                    $ar[$k] = [$ar[$k]];
                }

                $ar[$k][] = $child;
            }
        }

        return $ar;
    }

    /**
     * Process XML value, handling boolean, if appropriate.
     *
     * @param \SimpleXMLElement $valueElement The simplexml value object.
     *
     * @return string|float|int|bool string or boolean value
     */
    protected static function getConvertedXmlValue(SimpleXMLElement $valueElement)
    {
        $value = (string)$valueElement; // convert from simplexml to string

        //handle numeric values
        if (is_numeric($value)) {
            if (ctype_digit($value)) {
                return (int)$value;
            }

            return (float)$value;
        }

        // handle booleans specially
        if (strlen($value) <= 5) {
            switch (strtolower($value)) {
                case 'false':
                    return false;
                case 'true':
                    return true;
            }
        }

        return $value;
    }
}
