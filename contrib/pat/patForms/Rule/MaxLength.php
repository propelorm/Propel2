<?php

/**
 * patForms Rule MaxLength
 *
 * This is just a simple rule, that checks for a required minimum length of a field
 *
 * @package    patForms
 * @subpackage Rules
 * @author     Sven Fuchs <svenfuchs@artweb-design.de>
 * @license    LGPL, see license.txt for details
 * @link       http://www.php-tools.net
 */
class patForms_Rule_MaxLength extends patForms_Rule
{
	/**
	* script that will be displayed only once
	*
	* @access     private
	* @var        array
	*/
	var $globalScript = array(
		'html'	=>	"/* patForms::Rule::MaxLength */

function pFRC_MaxLength(field) {
	this.field = eval('pfe_' + field);
}

pFRC_MaxLength.prototype.validate = function() {
	value = this.field.getValue();
	if (value.length > this.value) {
		alert('Please enter a value that is max. ' + this.value + ' characters long.');
	}
}

pFRC_MaxLength.prototype.setValue = function(value) {
	this.value	= value;
}

/* END: patForms::Rule::MaxLength */
"
	);

	/**
	* javascript that will be displayed once per instance
	*
	* @access     private
	* @var        array
	*/
	var $instanceScript	= array(
		'html'	=>	"var pfr_[RULE::ID] = new pFRC_MaxLength('[CONTAINER::NAME]');\n"
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
	var	$ruleName = 'MaxLength';

	/**
	* define error codes and messages for the rule
	*
	* @access     private
	* @var        array	$validatorErrorCodes
	* @todo       translate error messages
	*/
	var	$validatorErrorCodes = array(
		"C"	=>	array(
			1	=>	"Please enter a value that is max. [VALUE] characters long.",
		),
		"de" =>	array(
			1	=>	"Bitte geben Sie einen max. [VALUE] Zeichen langen Wert ein.",
		),
		"fr" =>	array(
			1	=>	"Please enter a value that is max. [VALUE] characters long.",
		)
	);

	/**
	* possible values
	* @access     private
	* @var        array
	*/
	var $_values;

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
		$this->value = $value;
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

		if (strlen($element->getValue()) <= $this->value) {
			return	true;
		}

		$this->addValidationError(1, array('value' => $this->value));
		return false;
	}

	/**
	*
	*
	* @access     public
	*/
	function registerJavascripts(&$form) {

		parent::registerJavascripts($form);

		$script = sprintf("pfr_%s.setValue(%s);\n", $this->_id, $this->value);
		$form->registerInstanceJavascript($script);
	}
}
