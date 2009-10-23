<?php
/*
 *  $Id: Behavior.php 989 2008-03-11 14:29:30Z heltem $
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

include_once 'propel/engine/database/model/Index.php';

/**
 * Information about behaviors of a table.
 *
 * @author     François Zaninotto
 * @version    $Revision: 989 $
 * @package    propel.engine.database.model
 */
class Behavior extends XMLElement {

  protected $table;
  protected $database;
  protected $name;
  protected $parameters = array();
  
  public function setName($name)
  {
    $this->name = $name;
  }  
  
  public function getName()
  {
    return $this->name;
  }
  
  public function setTable(Table $table)
  {
    $this->table = $table;
  }

  public function getTable()
  {
    return $this->table;
  }

  public function setDatabase(Database $database)
  {
    $this->database = $database;
  }

  public function getDatabase()
  {
    return $this->database;
  }
  
  /**
   * Add a parameter
   * Expects an associative array looking like array('name' => 'foo', 'value' => bar)
   *
   * @param array associative array with name and value keys
   */
  public function addParameter($attribute)
  {
    $attribute = array_change_key_case($attribute, CASE_LOWER);
    $this->parameters[$attribute['name']] = $attribute['value'];
  }
  
  /**
   * Overrides the behavior parameters
   * Expects an associative array looking like array('foo' => 'bar')
   *
   * @param array associative array
   */
  public function setParameters($parameters)
  {
    $this->parameters = $parameters;
  }
  
  /**
   * Get the associative array of parameters
   * @return array 
   */
  public function getParameters()
  {
    return $this->parameters;
  }

  public function getParameter($name)
  {
    return $this->parameters[$name];
  }

  /**
   * This method is automatically called on database behaviors when the database model is finished
   * Propagate the behavior to the tables of the database
   * Override this method to have a database behavior do something special
   */
  public function modifyDatabase()
  {
    foreach ($this->getDatabase()->getTables() as $table)
    {
      $b = clone $this;
      $table->addBehavior($b);
    }
  }
  
  /**
   * This method is automatically called on table behaviors when the database model is finished
   * Override it to add columns to the current table
   */
  public function modifyTable()
  {
  }
  
  /**
   * Retrieve a column object using a name stored in the behavior parameters
   * Useful for table behaviors
   * 
   * @param  string    $param Name of the parameter storing the column name
   * @return ColumnMap        The column of the table supporting the behavior
   */
  public function getColumnForParameter($param)
  {
  	return $this->getTable()->getColumn($this->getParameter($param));
  }
  
  /**
   * Sets up the Behavior object based on the attributes that were passed to loadFromXML().
   * @see        parent::loadFromXML()
   */
  protected function setupObject()
  {
    $this->name = $this->getAttribute("name");
  }
    
  /**
   * @see        parent::appendXml(DOMNode)
   */
  public function appendXml(DOMNode $node)
  {
    $doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

    $bNode = $node->appendChild($doc->createElement('behavior'));
    $bNode->setAttribute('name', $this->getName());

    foreach ($this->parameters as $name => $value) {
      $parameterNode = $bNode->appendChild($doc->createElement('parameter'));
      $parameterNode->setAttribute('name', $name);
      $parameterNode->setAttribute('value', $value);
    }
  }
  
  public function getTableModifier()
  {
    return $this;
  }  
  
  public function getObjectBuilderModifier()
  {
    return $this;
  }

  public function getPeerBuilderModifier()
  {
    return $this;
  }
  
  public function getTableMapBuilderModifier()
  {
    return $this;
  }
}
