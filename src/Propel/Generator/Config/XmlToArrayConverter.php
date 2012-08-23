<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

/**
 * Runtime configuration converter
 * From XML string to array
 */
class XmlToArrayConverter
{
    /**
     * Create a PHP array from the XML configuration found in a runtime-conf.xml file
     *
     * @param String $configuration The XML configuration
     *
     * @return Array
     */
    public static function convert($configuration)
    {
        $xml = simplexml_load_string($configuration);
        $conf = self::simpleXmlToArray($xml);

        /* For some reason the array generated from runtime-conf.xml has separate
         * 'log' section and 'propel' sections. To maintain backward compatibility
         * we need to put 'log' back into the 'propel' section.
         */
        if (isset($conf['log'])) {
            $conf['propel']['log'] = $conf['log'];
            unset($conf['log']);
        }
        if (isset($conf['profiler'])) {
            $conf['propel']['profiler'] = $conf['profiler'];
            unset($conf['profiler']);
        }
        if (isset($conf['propel'])) {
            $conf = $conf['propel'];
        }

        return $conf;
    }

    /**
     * Recursive function that converts an SimpleXML object into an array.
     * @author     Christophe VG (based on code form php.net manual comment)
     *
     * @param      object SimpleXML object.
     * @return array Array representation of SimpleXML object.
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
     * @param      object The simplexml value object.
     * @return mixed string or boolean value
     */
    private static function getConvertedXmlValue($value)
    {
        $value = (string) $value; // convert from simplexml to string
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
