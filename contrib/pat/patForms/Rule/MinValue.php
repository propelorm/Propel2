<?php

/**
 * patForms Rule MinValue
 *
 * This rule simply checks for a required minimum value (number) of a field
 *
 * @package    patForms
 * @subpackage Rules
 * @author     Sven Fuchs <svenfuchs@artweb-design.de>
 * @license    LGPL, see license.txt for details
 * @link       http://www.php-tools.net
 */
class patForms_Rule_MinValue extends patForms_Rule
{
	/**
	* script that will be displayed only once
	*
	* @access     private
	* @var        array
	*/

	var $globalScript = array(
		'html'	=>	"/* patForms::Rule::MinValue */

function pFRC_MinValue(field) {
	this.field = eval('pfe_' + field);
}

pFRC_MinValue.prototype.validate = function() {
	value = this.field.getValue();
	if (parseInt(value) != value) {
		alert('Please enter a number that is greater or equal to ' + this.value);
	}
	if (parseInt(value) < this.value) {
		alert('Please enter a number that is greater or equal to ' + this.value);
	}
}

pFRC_MinValue.prototype.setMinValue = function(value) {
	this.value	= value;
}

/* END: patForms::Rule::MinValue */
"
	);

	/**
	* javascript that will be displayed once per instance
	*
	* @access     private
	* @var        array
	*/
	var $instanceScript	= array(
		'html'	=>	"var pfr_[RULE::ID] = new pFRC_MinValue('[CONTAINER::NAME]');\n"
	);

	/**
	* properties that have to be replaced in the instance script.
	*
	* @access     private
	* @var        array
	*/
	var $scriptPlaceholders	= array(
		'RULE::SOURCE'	=>	'_source',
	);

	/**
	* name of the rule
	*
	* @abstract
	* @access     private
	*/
	var	$ruleName = 'MinValue';

	/**
	* define error codes and messages for the rule
	*
	* @access     private
	* @var        array	$validatorErrorCodes
	* @todo       translate error messages
	*/
	var	$validatorErrorCodes = array(
		"C"	=>	array(
			1	=>	"Please enter a number that is greater or equal to [VALUE].",
		),
		"de" =>	array(
			1	=>	"Bitte geben Sie eine Zahl größer oder gleich [VALUE] ein.",
		),
		"fr" =>	array(
			1	=>	"Please enter a number that is greater or equal to [VALUE].",
		)
	);

	/**
	* the regEx pattern
	* @access     private
	* @var        string
	*/
	var $_value;

	/**
	* field id that is used
	* @access     private
	* @var        string
	*/
	var $_field;

	private $value = 10;

	public function __construct($params) {

		parent::__construct();

		extract($params);
		$this->_value = $value;
	}

	/**
	* prepare the rule
	*
	* @access     public
	* @param      object patForms
	*/
	function prepareRule(&$container) {

		patForms_Rule::prepareRule($container);

		$onChange = $container->getAttribute('onchange');
		$newHandler = sprintf('pfr_%s.validate();', $this->_id);
		$container->setAttribute('onchange', $newHandler . $onChange);

		return true;
	}

	/**
	* method called by patForms or any patForms_Element to validate the
	* element or the form.
	*
	* @access     public
	* @param      object patForms	form object
	*/
	function applyRule(&$element, $type = PATFORMS_RULE_AFTER_VALIDATION) {

		if (intval($element->getValue()) >= intval($this->_value)){
			return	true;
		}

		$this->addValidationError(1, array('value' => $this->_value));
		return false;
	}

	/**
	*
	*
	* @access     public
	*/
	function registerJavascripts(&$form) {

		parent::registerJavascripts($form);

		$script = sprintf("pfr_%s.setMinValue(%s);\n", $this->_id, $this->_value);
		$form->registerInstanceJavascript($script);
	}
}
