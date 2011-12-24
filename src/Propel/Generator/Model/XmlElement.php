<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\BehaviorNotFoundException;

/**
 * An abstract class for elements represented by XML tags (e.g. Column, Table).
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
abstract class XmlElement
{
    /**
     * The name => value attributes from XML.
     *
     * @var        array
     */
    protected $attributes = array();

    /**
     * Any associated vendor-specific information objects.
     *
     * @var        array VendorInfo[]
     */
    protected $vendorInfos = array();

    /**
     * Replaces the old loadFromXML() so that we can use loadFromXML() to load the attribs into the class.
     */
    abstract protected function setupObject();

    /**
     * This is the entry point method for loading data from XML.
     * It calls a setupObject() method that must be implemented by the child class.
     * @param      array $attributes The attributes for the XML tag.
     */
    public function loadFromXML($attributes)
    {
        $this->attributes = array_change_key_case($attributes, CASE_LOWER);
        $this->setupObject();
    }

    /**
     * Returns the assoc array of attributes.
     * All attribute names (keys) are lowercase.
     * @return     array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Gets a particular attribute by [case-insensitive] name.
     * If attribute is not set then the $defaultValue is returned.
     * @param      string $name The [case-insensitive] name of the attribute to lookup.
     * @param      mixed $defaultValue The default value to use in case the attribute is not set.
     * @return     mixed The value of the attribute or $defaultValue if not set.
     */
    public function getAttribute($name, $defaultValue = null)
    {
        $name = strtolower($name);
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        } else {
            return $defaultValue;
        }
    }

    /**
     * Converts value specified in XML to a boolean value.
     * This is to support the default value when used w/ a boolean column.
     * @return     value
     */
    protected function booleanValue($val)
    {
        if (is_numeric($val)) {
            return (bool) $val;
        } else {
            return (in_array(strtolower($val), array('true', 't', 'y', 'yes'), true) ? true : false);
        }
    }

    /**
     * Appends DOM elements to represent this object in XML.
     * @param      DOMNode $node
     */
    abstract public function appendXml(\DOMNode $node);

    /**
     * Sets an associated VendorInfo object.
     *
     * @param      mixed $data VendorInfo object or XML attrib data (array)
     * @return     VendorInfo
     */
    public function addVendorInfo($data)
    {
        if ($data instanceof VendorInfo) {
            $vi = $data;
            $this->vendorInfos[$vi->getType()] = $vi;

            return $vi;
        } else {
            $vi = new VendorInfo();
            $vi->loadFromXML($data);

            return $this->addVendorInfo($vi); // call self w/ different param
        }
    }

    /**
     * Gets the any associated VendorInfo object.
     * @return     VendorInfo
     */
    public function getVendorInfoForType($type)
    {
        if (isset($this->vendorInfos[$type])) {
            return $this->vendorInfos[$type];
        } else {
            // return an empty object
            return new VendorInfo($type);
        }
    }

    /**
     * Find the best class name for a given behavior
     * If not found, tries to autoload \Propel\Generator\Behavior\[Bname]\[Bname]Behavior
     * If no success, returns 'Behavior'
     *
     * @param  string $bname A behavior name, e.g. 'timestampable'
     * @return string A behavior class name, e.g. '\Propel\Generator\Behavior\Timestampable\TimestampableBehavior'
     */
    public function getConfiguredBehavior($bname)
    {
        if (false !== strpos($bname, '\\')) {
            $class = $bname;
        } else {
            $gen = new PhpNameGenerator();
            $phpName = $gen->generateName(array($bname, PhpNameGenerator::CONV_METHOD_PHPNAME));
            $class = sprintf('\\Propel\\Generator\\Behavior\\%s\\%sBehavior', $phpName, $phpName);
        }

        if (class_exists($class)) {
            return $class;
        }

        throw new BehaviorNotFoundException(sprintf('Unknown behavior "%s"', $bname));
    }

    /**
     * String representation of the current object.
     *
     * This is an xml representation with the XML declaration removed.
     *
     * @see        appendXml()
     */
    public function toString()
    {
        $doc = new \DOMDocument('1.0');
        $doc->formatOutput = true;
        $this->appendXml($doc);
        $xmlstr = $doc->saveXML();

        return trim(preg_replace('/<\?xml.*?\?>/', '', $xmlstr));
    }

    /**
     * Magic string method
     * @see toString()
     */
    public function __toString()
    {
        return $this->toString();
    }
}
