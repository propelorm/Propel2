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
 * Data about a validation rule used in an application.
 *
 * @author     Michael Aichler <aichler@mediacluster.de> (Propel)
 * @author     John McNally <jmcnally@collab.net> (Intake)
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class Rule extends XMLElement {

	private $name;
	private $value;
	private $message;
	private $validator;
	private $classname;

	/**
	 * Sets up the Rule object based on the attributes that were passed to loadFromXML().
	 * @see        parent::loadFromXML()
	 */
	protected function setupObject()
	{
		$this->name = $this->getAttribute("name");
		$this->value = $this->getAttribute("value");
		$this->classname = $this->getAttribute("class");

		/*
		* Set some default values if they are not specified.
		* This is escpecially useful for maxLength; the size
		* is already known by the column and this way it is
		* not necessary to manage the same size two times.
		*
		* Currently there is only one such supported default:
		*   - maxLength value = column max length
		*   (this default cannot be easily set at runtime w/o changing
		*   design of class system in undesired ways)
		*/
		if ($this->value === null) {
			switch($this->name) {
				case 'maxLength':
					$this->value = $this->validator->getColumn()->getSize();
					break;
			}
		}

		$this->message = $this->getAttribute("message");
	}

	/**
	 * Sets the owning validator for this rule.
	 * @param      Validator $validator
	 * @see        Validator::addRule()
	 */
	public function setValidator(Validator $validator)
	{
		$this->validator = $validator;
	}

	/**
	 * Gets the owning validator for this rule.
	 * @return     Validator
	 */
	public function getValidator()
	{
		return $this->validator;
	}

	/**
	 * Sets the dot-path name of class to use for rule.
	 * If no class is specified in XML, then a classname will
	 * be built based on the 'name' attrib.
	 * @param      string $classname dot-path classname (e.g. myapp.propel.MyValidator)
	 */
	public function setClass($classname)
	{
		$this->classname = $classname;
	}

	/**
	 * Gets the dot-path name of class to use for rule.
	 * If no class was specified, this method will build a default classname
	 * based on the 'name' attribute.  E.g. 'maxLength' -> 'propel.validator.MaxLengthValidator'
	 * @return     string dot-path classname (e.g. myapp.propel.MyValidator)
	 */
	public function getClass()
	{
		if ($this->classname === null && $this->name !== null) {
			return "propel.validator." . ucfirst($this->name) . "Validator";
		}
		return $this->classname;
	}

	/**
	 * Sets the name of the validator for this rule.
	 * This name is used to build the classname if none was specified.
	 * @param      string $name Validator name for this rule (e.g. "maxLength", "required").
	 * @see        getClass()
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Gets the name of the validator for this rule.
	 * @return     string Validator name for this rule (e.g. "maxLength", "required").
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Sets the value parameter for this validator rule.
	 * Note: not all validators need a value parameter (e.g. 'required' validator
	 * does not).
	 * @param      string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Gets the value parameter for this validator rule.
	 * @return     string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Sets the message that will be displayed to the user if validation fails.
	 * This message may be a Gettext msgid (if translation="gettext") or some other
	 * id for an alternative not-yet-supported translation system.  It may also
	 * be a simple, single-language string.
	 * @param      string $message
	 * @see        setTranslation()
	 */
	public function setMessage($message)
	{
		$this->message = $message;
	}

	/**
	 * Gets the message that will be displayed to the user if validation fails.
	 * This message may be a Gettext msgid (if translation="gettext") or some other
	 * id for an alternative not-yet-supported translation system.  It may also
	 * be a simple, single-language string.
	 * @return     string
	 * @see        setTranslation()
	 */
	public function getMessage()
	{
		$message = str_replace('${value}', $this->getValue(), $this->message);
		return $message;
	}

	/**
	 * @see        XMLElement::appendXml(DOMNode)
	 */
	public function appendXml(DOMNode $node)
	{
		$doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

		$ruleNode = $node->appendChild($doc->createElement('rule'));
		$ruleNode->setAttribute('name', $this->getName());

		if ($this->getValue() !== null) {
			$ruleNode->setAttribute('value', $this->getValue());
		}

		if ($this->classname !== null) {
			$ruleNode->setAttribute('class', $this->getClass());
		}

		$ruleNode->setAttribute('message', $this->getMessage());
	}

}
