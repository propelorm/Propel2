<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\InvalidArgumentException;

/**
 * An abstract model class to represent objects that belongs to a schema like
 * databases, tables, columns, indices, unices, foreign keys...
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
abstract class MappingModel implements MappingModelInterface
{
    /**
     * The list of attributes.
     *
     * @var array
     */
    private $attributes;

    /**
     * The list of vendor's information.
     *
     * @var array
     */
    private $vendorInfos;

    /**
     * The name of this element, used in sql statements.
     *
     * @var string
     */
    protected $sqlName;

    /**
     * The name of this element.
     *
     * @var string
     */
    protected $name;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->attributes  = [];
        $this->vendorInfos = [];
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

        return in_array(strtolower($value),  [ 'true', 't', 'y', 'yes' ], true);
    }

    protected function getDefaultValueForArray($stringValue)
    {
        $stringValue = trim($stringValue);

        if (empty($stringValue)) {
            return null;
        }

        $values = [];
        foreach (explode(',', $stringValue) as $v) {
            $values[] = trim($v);
        }

        $value = implode($values, ' | ');
        if (empty($value) || ' | ' === $value) {
            return null;
        }

        return sprintf('||%s||', $value);
    }

    /**
     * Adds a new VendorInfo instance to this current model object.
     *
     * @param  VendorInfo|array $vendor
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
     * Returns the list of all vendor information.
     *
     * @return VendorInfo[]
     */
    public function getVendorInformation()
    {
        return $this->vendorInfos;
    }

    /**
     * @inheritdoc
     */
    public function getSqlName()
    {
        if (!$this->sqlName) {
            if (null === $this->name) {
                throw new BuildException(
                    "Cannot create the `sqlName`: did you set the `name` of your " . get_class($this) . " object?");
            }
            $this->sqlName = NamingTool::toUnderscore($this->getName());
        }

        return $this->sqlName;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the element. It's forced to camelCase.
     * Override it for different behavior.
     *
     * @return string
     */
    public function setName($value)
    {
        if (!is_string($value) || '' == $value) {
            throw new InvalidArgumentException("The name of a `" . get_class($this) . "` must be a valid string.");
        }

        $this->name = NamingTool::toCamelCase($value);
    }

    /**
     * Set the name of this element, to be used in sql statements.
     * It's NOT forced to particular format.
     *
     * @param $value string
     */
    public function setSqlName($value)
    {
        if (null === $value || '' == $value) {
            $value = NamingTool::toUnderscore($this->getName());
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException("The name of a `" . get_class($this) . "` must be a valid string.");
        }

        $this->sqlName = $value;
    }
}
