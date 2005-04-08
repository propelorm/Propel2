<?php
/*
 *  $Id: Domain.php,v 1.3 2005/03/17 01:16:41 hlellelid Exp $
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

/**
 * A Class for holding data about a column used in an Application.
 *
 * @author  Hans Lellelid <hans@xmpl.org> (Propel)
 * @author  Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version $Revision: 1.3 $
 * @package propel.engine.database.model
 */
class Domain extends XMLElement {

    private $name;
    private $description;
    private $size;
    private $scale;
    
    /** type as defined in schema.xml */
    private $propelType;
    private $sqlType;
    private $defaultValue;          
    
	/** Database object -- in the event this Domain is specified in the XML. */
	private $database;
	
    /**
     * Creates a new Domain object.
     * If this domain needs a name, it must be specified manually.
     * 
     * @param string $type Propel type.
     * @param string $sqlType SQL type.
     * @param string $size
     * @param string $scale
     */
    public function __construct($type = null, $sqlType = null, $size = null, $scale = null)
    {
        $this->propelType = $type;
        $this->sqlType = ($sqlType !== null) ? $sqlType : $type;
        $this->size = $size;
        $this->scale = $scale;
    }

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
	 * @see parent::loadFromXML()
     */
    protected function setupObject()
    {    
        $schemaType = strtoupper($this->getAttribute("type"));
        $this->copy($this->getDatabase()->getPlatform()->getDomainForType($schemaType));
        
        //Name
        $this->name = $this->getAttribute("name");
        
        //Default column value.
        $this->defaultValue = $this->getAttribute("default"); // may need to adjust -- e.g. for boolean values        
        
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
     * @return Returns the description.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param description The description to set.
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return Returns the name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param name The name to set.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Returns the scale.
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param scale The scale to set.
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
    }
    
    /**
     * Replaces the size if the new value is not null.
     * 
     * @param value The size to set.
     */
    public function replaceScale($value)
    {
        if ($value !== null) {
            $this->scale = $value;
        }
    }

    /**
     * @return Returns the size.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param size The size to set.
     */
    public function setSize($size)
    {
        $this->size = $size;
    }
    
    /**
     * Replaces the size if the new value is not null.
     * 
     * @param value The size to set.
     */
    public function replaceSize($value)
    {
        if ($value !== null) {
            $this->size = $value;
        }
    }

    /**
     * @return string Returns the propelType.
     */
    public function getType()
    {
        return $this->propelType;
    }

    /**
     * @param string $propelType The PropelTypes type to set.
     */
    public function setType($propelType)
    {
        $this->propelType = $propelType;
    }  
    
    /**
     * Replaces the default value if the new value is not null.
     * 
     * @param value The defaultValue to set.
     */
    public function replaceType($value)
    {
        if ($value !== null) {
            $this->propelType = $value;
        }       
    }
    
    /**
     * @return string Returns the defaultValue.
     */
    public function getDefaultValue()
    {
		if ($this->defaultValue === null) {
			return null;
		} elseif ($this->propelType === PropelTypes::BOOLEAN) {
			// convert "true" => TRUE
            return $this->booleanValue($this->defaultValue);
		} elseif ($this->propelType === PropelTypes::DATE || $this->propelType === PropelTypes::TIME || $this->propelType === PropelTypes::TIMESTAMP) {
			// DATE/TIME vals need to be converted to integer timestamp
			$ts = strtotime($this->defaultValue);
			if ($ts === -1) {
				throw new EngineException("Unable to parse default value for ".$table->getName().".".$col->getName()." as date/time value: " . var_export($val, true));
			}
			return $ts;
		} else {
			return $this->defaultValue;
		}
    }
     
    /**
     * @param defaultValue The defaultValue to set.
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }
    
    /**
     * Replaces the default value if the new value is not null.
     * 
     * @param string $value The defaultValue to set.
     */
    public function replaceDefaultValue($value)
    {
        if ($value !== null) {
            $this->defaultValue = $value;
        }         
    }

    /**
     * @return Returns the sqlType.
     */
    public function getSqlType() 
    {
        return $this->sqlType;
    }

    /**
     * @param string $sqlType The sqlType to set.
     */
    public function setSqlType($sqlType) 
    {
        $this->sqlType = $sqlType;
    }

    /**
     * Return the size and scale in brackets for use in an sql schema.
     * 
     * @return size and scale or an empty String if there are no values 
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
