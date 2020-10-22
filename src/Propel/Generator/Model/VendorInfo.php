<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    /**
     * @var string|null
     */
    private $type;

    /**
     * @var array
     */
    private $parameters;

    /**
     * Creates a new VendorInfo instance.
     *
     * @param string|null $type RDBMS type (optional)
     * @param array $parameters An associative array of vendor's parameters (optional)
     */
    public function __construct($type = null, array $parameters = [])
    {
        $this->parameters = [];

        if ($type !== null) {
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
     *
     * @return void
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
     * @param string $name The parameter name
     * @param mixed $value The parameter value
     *
     * @return void
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Returns a parameter value.
     *
     * @param string $name The parameter name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * Returns whether or not a parameter exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Sets an associative array of parameters for vendor specific information.
     *
     * @param array $parameters Parameter data.
     *
     * @return void
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
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->parameters);
    }

    /**
     * Returns a new VendorInfo object that combines two VendorInfo objects.
     *
     * @param \Propel\Generator\Model\VendorInfo $info
     *
     * @return \Propel\Generator\Model\VendorInfo
     */
    public function getMergedVendorInfo(VendorInfo $info)
    {
        $params = array_merge($this->parameters, $info->getParameters());

        $newInfo = new VendorInfo($this->type);
        $newInfo->setParameters($params);

        return $newInfo;
    }

    /**
     * @return void
     */
    protected function setupObject()
    {
        $this->type = $this->getAttribute('type');
    }
}
