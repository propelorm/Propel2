<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'propel/engine/database/model/XMLElement.php';
include_once 'propel/engine/EngineException.php';

/**
 * Object to hold vendor-specific info.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class VendorInfo extends XMLElement {

	/**
	 * The vendor RDBMS type.
	 *
	 * @var        string
	 */
	private $type;

	/**
	 * Vendor parameters.
	 *
	 * @var        array
	 */
	private $parameters = array();

	/**
	 * Creates a new VendorInfo instance.
	 *
	 * @param      string $type RDBMS type (optional)
	 */
	public function __construct($type = null)
	{
		$this->type = $type;
	}

	/**
	 * Sets up this object based on the attributes that were passed to loadFromXML().
	 * @see        parent::loadFromXML()
	 */
	protected function setupObject()
	{
		$this->type = $this->getAttribute("type");
	}

	/**
	 * Set RDBMS type for this vendor-specific info.
	 *
	 * @param      string $v
	 */
	public function setType($v)
	{
		$this->type = $v;
	}

	/**
	 * Get RDBMS type for this vendor-specific info.
	 *
	 * @return     string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Adds a new vendor parameter to this object.
	 * @param      array $attrib Attributes from XML.
	 */
	public function addParameter($attrib)
	{
		$name = $attrib["name"];
		$this->parameters[$name] = $attrib["value"];
	}

	/**
	 * Sets parameter value.
	 *
	 * @param      string $name
	 * @param      mixed $value The value for the parameter.
	 */
	public function setParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	/**
	 * Gets parameter value.
	 *
	 * @param      string $name
	 * @return     mixed Paramter value.
	 */
	public function getParameter($name)
	{
		if (isset($this->parameters[$name])) {
			return $this->parameters[$name];
		}
		return null; // just to be explicit
	}

	/**
	 * Whether parameter exists.
	 *
	 * @param      string $name
	 */
	public function hasParameter($name)
	{
		return isset($this->parameters[$name]);
	}

	/**
	 * Sets assoc array of parameters for venfor specific info.
	 *
	 * @param      array $params Paramter data.
	 */
	public function setParameters(array $params = array())
	{
		$this->parameters = $params;
	}

	/**
	 * Gets assoc array of parameters for venfor specific info.
	 *
	 * @return     array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Gets a new merged VendorInfo object.
	 * @param      VendorInfo $info
	 * @return     VendorInfo new object with merged parameters
	 */
	public function getMergedVendorInfo(VendorInfo $merge)
	{
		$newParams = array_merge($this->getParameters(), $merge->getParameters());
		$newInfo = new VendorInfo($this->getType());
		$newInfo->setParameters($newParams);
		return $newInfo;
	}

	/**
	 * @see        XMLElement::appendXml(DOMNode)
	 */
	public function appendXml(DOMNode $node)
	{
		$doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

		$vendorNode = $node->appendChild($doc->createElement("vendor"));
		$vendorNode->setAttribute("type", $this->getType());

		foreach ($this->parameters as $key => $value) {
			$parameterNode = $doc->createElement("parameter");
			$parameterNode->setAttribute("name", $key);
			$parameterNode->setAttribute("value", $value);
			$vendorNode->appendChild($parameterNode);
		}
	}
}
