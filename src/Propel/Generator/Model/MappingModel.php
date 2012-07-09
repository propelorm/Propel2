<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\BehaviorNotFoundException;

/**
 * An abstract model class to represent objects that belongs to a schema like
 * databases, tables, columns, indices, unices, foreign keys...
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
abstract class MappingModel implements MappingModelInterface
{
    protected $attributes;
    protected $vendorInfos;

    public function __construct()
    {
        $this->attributes  = array();
        $this->vendorInfos = array();
    }

    /**
     * Loads a mapping definition from an array.
     *
     * @param array $attributes
     */
    public function loadMapping(array $attributes)
    {
        $this->attributes = array_change_key_case($attributes, CASE_LOWER);
        $this->setupObject();
    }

    /**
     * This method must be implemented by children classes to hydrate and
     * configure the current object with the loaded mapping definition stored in
     * the protected $attributes array.
     */
    abstract protected function setupObject();

    /**
     * Returns all definition attributes.
     *
     * All attribute names (keys) are lowercase.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns a particular attribute by a case-insensitive name.
     *
     * If the attribute is not set, then the second default value is
     * returned instead.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        $name = strtolower($name);
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * Converts a value (Boolean, string or numeric) into a Boolean value.
     *
     * This is to support the default value when used with a boolean column.
     *
     * @param  mixed   $value
     * @return boolean
     */
    protected function booleanValue($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (Boolean) $value;
        }

        return in_array(strtolower($value), array('true', 't', 'y', 'yes'), true);
    }

    /**
     * Appends DOM elements to represent this object in XML.
     *
     * @param \DOMNode $node
     */
    abstract public function appendXml(\DOMNode $node);

    /**
     * Adds a new VendorInfo instance to this current model object.
     *
     * @param  VendorInfo|array $data
     * @return VendorInfo
     */
    public function addVendorInfo($vendor)
    {
        if ($vendor instanceof VendorInfo) {
            $this->vendorInfos[$vendor->getType()] = $vendor;

            return $vendor;
        }

        $vi = new VendorInfo();
        $vi->loadMapping($vendor);

        return $this->addVendorInfo($vi);
    }

    /**
     * Returns a VendorInfo object by its type.
     *
     * @param  string     $type
     * @return VendorInfo
     */
    public function getVendorInfoForType($type)
    {
        if (isset($this->vendorInfos[$type])) {
            return $this->vendorInfos[$type];
        }

        return new VendorInfo($type);
    }

    /**
     * Returns the best class name for a given behavior.
     *
     * If not found, the method tries to autoload \Propel\Generator\Behavior\[Bname]\[Bname]Behavior
     *
     * @param  string                    $behavior The behavior name (ie: timestampable)
     * @return string                    $class The behavior fully qualified class name
     * @throws BehaviorNotFoundException
     */
    public function getConfiguredBehavior($behavior)
    {
        if (false !== strpos($behavior, '\\')) {
            $class = $behavior;
        } else {
            $generator = new PhpNameGenerator();
            $phpName = $generator->generateName(array($behavior, PhpNameGenerator::CONV_METHOD_PHPNAME));
            $class = sprintf('\\Propel\\Generator\\Behavior\\%s\\%sBehavior', $phpName, $phpName);
        }

        if (!class_exists($class)) {
            throw new BehaviorNotFoundException(sprintf('Unknown behavior "%s"', $behavior));
        }

        return $class;
    }

    /**
     * String representation of the current object.
     *
     * @return string
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
     * String representation of the current object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
