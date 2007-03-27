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

/**
 * A class for holding data about a domain used in the schema.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class Domain extends XMLElement {

	/**
	 * @var        string The name of this domain
	 */
	private $name;

	/**
	 * @var        string Description for this domain.
	 */
	private $description;

	/**
	 * @var        int Size
	 */
	private $size;

	/**
	 * @var        int Scale
	 */
	private $scale;

	/**
	 * @var        int Propel type from schema
	 */
	private $propelType;

	/**
	 * @var        string The SQL type to use for this column
	 */
	private $sqlType;

	/**
	 * @var        ColumnDefaultValue A default value
	 */
	private $defaultValue;

	/**
	 * @var        Database
	 */
	private $database;

	/**
	 * Creates a new Domain object.
	 * If this domain needs a name, it must be specified manually.
	 *
	 * @param      string $type Propel type.
	 * @param      string $sqlType SQL type.
	 * @param      string $size
	 * @param      string $scale
	 */
	public function __construct($type = null, $sqlType = null, $size = null, $scale = null)
	{
		$this->propelType = $type;
		$this->sqlType = ($sqlType !== null) ? $sqlType : $type;
		$this->size = $size;
		$this->scale = $scale;
	}

	/**
	 * Copy the values from current object into passed-in Domain.
	 * @param      Domain $domain Domain to copy values into.
	 */
	public function copy(Domain $domain)
	{
		$this->defaultValue = $domain->getDefaultValue();
		$this->description = $domain->getDescription();
		$this->name = $domain->getName();
		$this->scale = $domain->getScale();
		$this->size = $domain->getSize();
		$this->sqlType = $domain->getSqlType();
		$this->propelType = $domain->getType();
	}

	/**
	 * Sets up the Domain object based on the attributes that were passed to loadFromXML().
	 * @see        parent::loadFromXML()
	 */
	protected function setupObject()
	{
		$schemaType = strtoupper($this->getAttribute("type"));
		$this->copy($this->getDatabase()->getPlatform()->getDomainForType($schemaType));

		//Name
		$this->name = $this->getAttribute("name");

		//Default column value.
		$this->defaultValue = $this->getAttribute("default"); // may need to adjust -- e.g. for boolean values
		$defType = strtolower($this->getAttribute("defaultType"));
		if (!empty($defType)) {
			if ($defType == self::DEFAULTTYPE_EXPR || $defType == self::DEFAULTTYPE_VALUE) {
				$this->defaultValueType = $defType;
			} else {
				throw new EngineException("Invalid value for defaultType: " . $defType);
			}
		}

		$this->size = $this->getAttribute("size");
		$this->scale = $this->getAttribute("scale");
		$this->description = $this->getAttribute("description");
	}

	/**
	 * Sets the owning database object (if this domain is being setup via XML).
	 */
	public function setDatabase(Database $database) {
		$this->database = $database;
	}

	/**
	 * Gets the owning database object (if this domain was setup via XML).
	 */
	public function getDatabase() {
		return $this->database;
	}

	/**
	 * @return     Returns the description.
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param      description The description to set.
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return     Returns the name.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param      name The name to set.
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return     Returns the scale.
	 */
	public function getScale()
	{
		return $this->scale;
	}

	/**
	 * @param      scale The scale to set.
	 */
	public function setScale($scale)
	{
		$this->scale = $scale;
	}

	/**
	 * Replaces the size if the new value is not null.
	 *
	 * @param      value The size to set.
	 */
	public function replaceScale($value)
	{
		if ($value !== null) {
			$this->scale = $value;
		}
	}

	/**
	 * @return     Returns the size.
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @param      size The size to set.
	 */
	public function setSize($size)
	{
		$this->size = $size;
	}

	/**
	 * Replaces the size if the new value is not null.
	 *
	 * @param      value The size to set.
	 */
	public function replaceSize($value)
	{
		if ($value !== null) {
			$this->size = $value;
		}
	}

	/**
	 * @return     string Returns the propelType.
	 */
	public function getType()
	{
		return $this->propelType;
	}

	/**
	 * @param      string $propelType The PropelTypes type to set.
	 */
	public function setType($propelType)
	{
		$this->propelType = $propelType;
	}

	/**
	 * Replaces the default value if the new value is not null.
	 *
	 * @param      value The defaultValue to set.
	 */
	public function replaceType($value)
	{
		if ($value !== null) {
			$this->propelType = $value;
		}
	}

	/**
	 * Gets the default value object.
	 * @return     ColumnDefaultValue The default value object for this domain.
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * Gets the default value, type-casted for use in PHP OM.
	 * @return     mixed
	 * @see        getDefaultValue()
	 */
	public function getPhpDefaultValue()
	{
		if ($this->defaultValue === null) {
			return null;
		} else {
			if ($this->defaultValue->isExpression()) {
				throw new EngineException("Cannot get PHP version of default value for default value EXPRESSION.");
			}
			if ($this->propelType === PropelTypes::BOOLEAN) {
				return $this->booleanValue($this->defaultValue->getValue());
			} elseif (PropelTypes::isTemporalType($this->propelType)) {
				return new DateTime($this->defaultValue->getValue());
			} else {
				return $this->defaultValue->getValue();
			}
		}
	}

	/**
	 * @param      ColumnDefaultValue $value The column default value to set.
	 */
	public function setDefaultValue(ColumnDefaultValue $value)
	{
		$this->defaultValue = $value;
	}

	/**
	 * Replaces the default value if the new value is not null.
	 *
	 * @param      ColumnDefaultValue $value The defualt value object
	 */
	public function replaceDefaultValue(ColumnDefaultValue $value = null)
	{
		if ($value !== null) {
			$this->defaultValue = $value;
		}
	}

	/**
	 * @return     Returns the sqlType.
	 */
	public function getSqlType()
	{
		return $this->sqlType;
	}

	/**
	 * @param      string $sqlType The sqlType to set.
	 */
	public function setSqlType($sqlType)
	{
		$this->sqlType = $sqlType;
	}

	/**
	 * Replaces the SQL type if the new value is not null.
	 * @param      string $sqlType The native SQL type to use for this domain.
	 */
	public function replaceSqlType($sqlType)
	{
		if ($sqlType !== null) {
			$this->sqlType = $sqlType;
		}
	}

	/**
	 * Return the size and scale in brackets for use in an sql schema.
	 *
	 * @return     size and scale or an empty String if there are no values
	 *         available.
	 */
	public function printSize()
	{
		if ($this->size !== null && $this->scale !== null)  {
			return '(' . $this->size . ',' . $this->scale . ')';
		} elseif ($this->size !== null) {
			return '(' . $this->size . ')';
		} else {
			return "";
		}
	}

}
