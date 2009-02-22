<?php

class PropelConfiguration implements ArrayAccess
{
	const TYPE_ARRAY = 1;

	const TYPE_ARRAY_FLAT = 2;

	const TYPE_OBJECT = 3;

	/**
	* @var        array An array of parameters
	*/
	protected $parameters = array();

	public function __construct(array $parameters = array())
	{
		$this->parameters = $parameters;
	}

	public function offsetExists($offset)
	{
		return isset($this->parameter[$offset]) || array_key_exists($offset, $this->parameters);
	}

	public function offsetSet($offset, $value)
	{
		$this->parameter[$offset] = $value;
	}

	public function offsetGet($offset)
	{
		return $this->parameters[$offset];
	}

	public function offsetUnset($offset)
	{
		unset($this->parameters[$offset]);
	}

	/**
	 * Get parameter value from the container
	 *
	 * @param      string $name
	 * @param      mixed $default Default value to be used if the
	 *                            requested value is not found
	 * @return     mixed Parameter value or the default
	 */
	public function getParameter($name, $default = null)
	{
		$parts = explode('.', $name); //name.space.name
		$name =  array_pop($parts); //actual name

		$section = $this->parameters;
		for($i=0; $i<count($parts); ++$i) {
			if(isset($section[$parts[$i]])) {
				$section = $section[$parts[$i]];
			}
			else { //namespace was not found, return default
				return $default;
			}
		}

		if(isset($section[$name]) || array_key_exists($name, $section)) {
			return $section[$name];
		}

		return $default;
	}

	/**
	 * Store a value to the container
	 *
	 * @param      string $name Configuration item name (name.space.name)
	 * @param      mixed $value Value to be stored
	 */
	public function setParameter($name, $value)
	{
		$parts = explode('.', $name); //name.space.name
		$name =  array_pop($parts); //actual name

		$section =& $this->parameters;
		for($i=0; $i<count($parts); ++$i) {
			if(!isset($section[$parts[$i]]) || !is_array($section[$parts[$i]])) { //yes, this will overwrite if the namespace was used for a scalar value
				$section[$parts[$i]] = array();
			}
			$section =& $section[$parts[$i]];
		}
		$section[$name] = $value;
	}

	/**
	 *
	 *
	 * @param      int $type
	 * @return     mixed
	 */
	public function getParameters($type = PropelConfiguration::TYPE_ARRAY)
	{
		switch ($type) {
			case PropelConfiguration::TYPE_ARRAY:
				return $this->parameters;
			case PropelConfiguration::TYPE_ARRAY_FLAT:
				return $this->toFlatArray();
			case PropelConfiguration::TYPE_OBJECT:
				return $this;
		}

	}

	protected function toFlatArray()
	{
		throw new PropelException('Configuration array flatning not yet supported');
	}

}

?>
