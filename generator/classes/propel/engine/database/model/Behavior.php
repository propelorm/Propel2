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

	public function addParameter($attribute)
	{
	  $attribute = array_change_key_case($attribute, CASE_LOWER);
	  $this->parameters[$attribute['name']] = $attribute['value'];
	}
	
	public function getParameters()
	{
	  return $this->parameters;
	}

  public function getParameter($name)
  {
    return $this->parameters[$name];
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
}
