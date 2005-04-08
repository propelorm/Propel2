<?php
/*
 *  $Id: Validator.php,v 1.3 2005/03/16 03:57:54 hlellelid Exp $
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
include_once 'propel/engine/database/model/PropelTypes.php';
include_once 'propel/engine/database/model/Rule.php';

/**
 * Validator.
 *
 * @author Michael Aichler <aichler@mediacluster.de> (Propel)
 * @version $Revision: 1.3 $
 * @package propel.engine.database.model
 */
class Validator extends XMLElement {

    const TRANSLATE_NONE = "none";
    const TRANSLATE_GETTEXT = "gettext";

    private $columnName;
    private $column;
    private $ruleList;
    private $translate;
    private $table;
	
    /**
     * Creates a new column and set the name
     *
     * @param name validator name
     */
    public function __construct()
    {
        $this->ruleList = array();
    }

    /**
     * Sets up the Validator object based on the attributes that were passed to loadFromXML().
	 * @see parent::loadFromXML()
     */
    protected function setupObject()
    {
        $this->columnName = $this->getAttribute("column");
        $this->translate = $this->getAttribute("translate", $this->getTable()->getDatabase()->getDefaultTranslateMethod());;
    }
    
    /**
     * Add a Rule to this validator.
     * Supports two signatures:
     * - addRule(Rule $rule)
     * - addRule(array $attribs)
     * @param mixed $data Rule object or XML attribs (array) from <rule/> element.
     * @return Rule The added Rule.
     */
    public function addRule($data)
    {
        if ($data instanceof Rule) {
            $rule = $data; // alias
            $rule->setValidator($this);
            $this->ruleList[] = $rule;
            return $rule;
        }
        else {
            $rule = new Rule();
            $rule->setValidator($this);
            $rule->loadFromXML($data);
            return $this->addRule($rule); // call self w/ different param
        }
    }
    
    /**
     * Gets an array of all added rules for this validator.
     * @return array Rule[]
     */
    public function getRules()
    {
        return $this->ruleList;
    }
    
    /**
     * Gets the name of the column that this Validator applies to.
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }
    
    /**
     * Sets the Column object that this validator applies to.
     * @param Column $column
     * @see Table::addValidator()
     */
    public function setColumn(Column $column)
    {
        $this->column = $column;
    }
    
    /**
     * Gets the Column object that this validator applies to.
     * @return Column
     */
    public function getColumn()
    {
        return $this->column;
    }
	
	/**
	 * Set the owning Table.
	 * @param Table $table
	 */
	public function setTable(Table $table)
	{
		$this->table = $table;
	}
	
	/**
	 * Get the owning Table.
	 * @return Table
	 */
	public function getTable()
	{
		return $this->table;
	}

    /**
     * Set the translation mode to use for the message.
     * Currently only "gettext" and "none" are supported.  The default is "none".
     * @param string $method Translation method ("gettext", "none").
     */
    public function setTranslate($method)
    {
        $this->translate = $method;
    }
    
    /**
     * Get the translation mode to use for the message.
     * Currently only "gettext" and "none" are supported.  The default is "none".
     * @return string Translation method ("gettext", "none").
     */
    public function getTranslate()
    {
        return $this->translate;
    }
    
    /**
     * Gets XML (string) representation of this Validator.
     * @return string
     */
    public function toString()
    {
        $result = "<validator column=\"" . $this->columnName . "\"";
        if ($this->translate !== null) {
            $result .= " translate=\"".$this->translate."\"";
        }
        $result .= ">\n";
        
        if ($this->ruleList !== null) {
            for($i=0,$_i=count($this->ruleList); $i < $_i; $i++) {
                $result .= $this->ruleList[$i]->toString();
            }
        }
        
        $result .= "</validator>\n";
        
        return $result;
    }
}