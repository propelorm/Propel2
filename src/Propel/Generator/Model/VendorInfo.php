<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\EngineException;

/**
 * Object to hold vendor specific information.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class VendorInfo extends XmlElement
{
    private $type;
    private $parameters;

    /**
     * Creates a new VendorInfo instance.
     *
     * @param string $type RDBMS type (optional)
     */
    public function __construct($type = null)
    {
        $this->parameters = array();

        if (null !== $type) {
            $this->setType($type);
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
     * @param string $name
     * @return Boolean
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Sets an associative array of parameters for venfor specific information.
     *
     * @param array $params Paramter data.
     */
    public function setParameters(array $parameters = array())
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns an associative array of parameters for
     * venfor specific information.
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
     * @return Boolean
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

    /**
     * Sets up this object based on the attributes that were passed to loadFromXML().
     *
     * @see parent::loadFromXML()
     */
    protected function setupObject()
    {
        $this->type = $this->getAttribute('type');
    }

    /**
     * @see XmlElement::appendXml(DOMNode)
     */
    public function appendXml(\DOMNode $node)
    {
        $doc = ($node instanceof \DOMDocument) ? $node : $node->ownerDocument;

        $vendorNode = $node->appendChild($doc->createElement('vendor'));
        $vendorNode->setAttribute('type', $this->type);

        foreach ($this->parameters as $key => $value) {
            $parameterNode = $doc->createElement('parameter');
            $parameterNode->setAttribute('name', $key);
            $parameterNode->setAttribute('value', $value);
            $vendorNode->appendChild($parameterNode);
        }
    }
}