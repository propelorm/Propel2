<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * PropelConfiguration is a container for all Propel's runtime configuration data.
 *
 * PropelConfiguration implements ArrayAccess interface so the configuration
 * can be accessed as an array or using a simple getter and setter. The whole
 * configuration can also be retrieved as a nested arrays, flat array or as a
 * PropelConfiguration instance.
 *
 * @author     Veikko Mäkinen <veikko@veikko.fi>
 * @version    $Revision$
 * @package    propel.runtime.config
 */
class PropelConfiguration implements ArrayAccess
{
	const TYPE_ARRAY = 1;
	const TYPE_ARRAY_FLAT = 2;
	const TYPE_OBJECT = 3;

	protected $parameters = array();
	protected $flattenedParameters = array();
	protected $isFlattened = false;

	/**
	 * Construct a new configuration container
	 *
	 * @param     array  $parameters
	 */
	public function __construct(array $parameters = array())
	{
		$this->parameters = $parameters;
	}

	/**
	 * @see       http://www.php.net/ArrayAccess
	 *
	 * @param     integer  $offset
	 * @return    boolean
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->parameters);
	}

	/**
	 * @see       http://www.php.net/ArrayAccess
	 *
	 * @param     integer  $offset
	 * @param     mixed    $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->parameters[$offset] = $value;
	}

	/**
	 * @see       http://www.php.net/ArrayAccess
	 *
	 * @param     integer  $offset
	 * @return    array
	 */
	public function offsetGet($offset)
	{
		return $this->parameters[$offset];
	}

	/**
	 * @see       http://www.php.net/ArrayAccess
	 *
	 * @param     integer  $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->parameters[$offset]);
	}

	/**
	 * Get parameter value from the container
	 *
	 * @param     string  $name  Parameter name
	 * @param     mixed   $default  Default value to be used if the requested value is not found
	 *
	 * @return    mixed  Parameter value or the default
	 */
	public function getParameter($name, $default = null)
	{
		$flattenedParameters = $this->getFlattenedParameters();
		if (isset($flattenedParameters[$name])) {
			return $flattenedParameters[$name];
		}
		return $default;
	}

	/**
	 * Store a value to the container
	 *
	 * @param     string  $name  Configuration item name (name.space.name)
	 * @param     mixed   $value  Value to be stored
	 */
	public function setParameter($name, $value)
	{
		$param = &$this->parameters;
		$parts = explode('.', $name); //name.space.name
		while ($part = array_shift($parts)) {
			$param = &$param[$part];
		}
		$param = $value;
		$this->flattenedParameters[$name] = $value;
	}

	/**
	 * @throws     PropelException
	 *
	 * @param     integer  $type
	 * @return    mixed
	 */
	public function getParameters($type = PropelConfiguration::TYPE_ARRAY)
	{
		switch ($type) {
			case PropelConfiguration::TYPE_ARRAY:
				return $this->parameters;
			case PropelConfiguration::TYPE_ARRAY_FLAT:
				return $this->getFlattenedParameters();
			case PropelConfiguration::TYPE_OBJECT:
				return $this;
			default:
				throw new PropelException('Unknown configuration type: '. var_export($type, true));
		}
	}

	/**
	 * @return    array
	 */
	public function getFlattenedParameters()
	{
		if (!$this->isFlattened) {
			$this->flattenParameters();
			$this->isFlattened = true;
		}
		return $this->flattenedParameters;
	}

	protected function flattenParameters()
	{
		$result = array();
		$it = new PropelConfigurationIterator(new RecursiveArrayIterator($this->parameters), RecursiveIteratorIterator::SELF_FIRST);
		foreach($it as $key => $value) {
			$ns = $it->getDepth() ? $it->getNamespace() . '.'. $key : $key;
			if ($it->getNodeType() == PropelConfigurationIterator::NODE_ITEM) {
				$result[$ns] = $value;
			}
		}
		$this->flattenedParameters = array_merge($this->flattenedParameters, $result);
	}
}
