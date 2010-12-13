<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/XMLElement.php';

/**
 * Data about an element with a name and optional namespace/schema/package attributes
 *
 * @author     Ulf Hermann <ulfhermann@kulturserver.de>
 * @version    $Revision$
 * @package    propel.generator.model
 */
abstract class ScopedElement extends XMLElement
{
	/**
	 * The package for the generated OM.
	 *
	 * @var       string
	 */
	protected $pkg;

	/**
	 * Namespace for the generated OM.
	 *
	 * @var       string
	 */
	protected $namespace;

	/**
	 * Schema this element belongs to.
	 *
	 * @var       string
	 */
	protected $schema;

	/**
	 * retrieves a build property.
	 *
	 * @param unknown_type $name
	 */
	abstract protected function getBuildProperty($name);

	/**
	 * Sets up the Rule object based on the attributes that were passed to loadFromXML().
	 * @see       parent::loadFromXML()
	 */
	protected function setupObject()
	{
		$this->schema = $this->getAttribute("schema", $this->schema);

		$this->namespace = $this->getAttribute("namespace", $this->namespace);
		$this->pkg = $this->getAttribute("package", $this->pkg);

		if ($this->schema && !$this->namespace && $this->getBuildProperty('schemaAutoNamespace')) {
			$this->namespace = $this->schema;
		}
		/* namespace.autoPackage overrides schema.autoPackage */
		if ($this->namespace && !$this->pkg && $this->getBuildProperty('namespaceAutoPackage')) {
			$this->pkg = str_replace('\\', '.', $this->namespace);
		} else if ($this->schema && !$this->pkg && $this->getBuildProperty('schemaAutoPackage')) {
			$this->pkg = $this->schema;
		}
	}

	/**
	 * Get the value of the namespace.
	 * @return     value of namespace.
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}

	/**
	 * Set the value of the namespace.
	 * @param      v  Value to assign to namespace.
	 */
	public function setNamespace($v)
	{
		$this->namespace = $v;
	}

	/**
	 * Get the value of package.
	 * @return     value of package.
	 */
	public function getPackage()
	{
		return $this->pkg;
	}

	/**
	 * Set the value of package.
	 * @param      v  Value to assign to package.
	 */
	public function setPackage($v)
	{
		$this->pkg = $v;
	}

	/**
	 * Get the value of schema.
	 * @return     value of schema.
	 */
	public function getSchema()
	{
		return $this->schema;
	}

	/**
	 * Set the value of schema.
	 * @param      v  Value to assign to schema.
	 */
	public function setSchema($v)
	{
		$this->schema = $v;
	}
}