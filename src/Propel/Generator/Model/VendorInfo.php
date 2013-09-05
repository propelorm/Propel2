<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * Object to hold vendor specific information.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class VendorInfo extends MappingModel
{
    private $type;
    private $parameters;

    /**
     * Creates a new VendorInfo instance.
     *
     * @param string $type       RDBMS type (optional)
     * @param array  $parameters An associative array of vendor's parameters (optional)
     */
    public function __construct($type = null, array $parameters = array())
    {
        parent::__construct();

        $this->parameters = [];

        if (null !== $type) {
            $this->setType($type);
        }

        if ($parameters) {
            $this->setParameters($parameters);
        }
    }

    /**
     * Sets the RDBMS type for this vendor specific information.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the RDBMS type for this vendor specific information.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets a parameter value.
     *
     * @param string $name  The parameter name
     * @param mixed  $value The parameter value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Returns a parameter value.
     *
     * @param  string $name The parameter name
     * @return mixed
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * Returns whether or not a parameter exists.
     *
     * @param  string  $name
     * @return boolean
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Sets an associative array of parameters for vendor specific information.
     *
     * @param array $parameters Parameter data.
     */
    public function setParameters(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns an associative array of parameters for
     * vendor specific information.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns whether or not this vendor info is empty.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->parameters);
    }

    /**
     * Returns a new VendorInfo object that combines two VendorInfo objects.
     *
     * @param  VendorInfo $info
     * @return VendorInfo
     */
    public function getMergedVendorInfo(VendorInfo $info)
    {
        $params = array_merge($this->parameters, $info->getParameters());

        $newInfo = new VendorInfo($this->type);
        $newInfo->setParameters($params);

        return $newInfo;
    }

    protected function setupObject()
    {
        $this->type = $this->getAttribute('type');
    }
}
