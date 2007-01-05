<?php
/**
 * patForms rule base class
 *
 * $Id$
 *
 * @access     protected
 * @package    patForms
 * @subpackage Rules
 */

/**
 * patForms rule base class
 *
 * @access     protected
 * @package    patForms
 * @subpackage Rules
 * @author     Stephan Schmidt <schst@php-tools.net>
 * @license    LGPL, see license.txt for details
 * @link       http://www.php-tools.net
 * @todo       implement javascript helper methods (set a javascript property plus an
 *				array of keys that will be replaced by the properties of the rule)
 */
class patForms_Rule
{
   /**
	* time when the rule should be applied
	*
	* Possible values are:
	* -PATFORMS_RULE_BEFORE_VALIDATION
	* -PATFORMS_RULE_AFTER_VALIDATION
	* -PATFORMS_RULE_BOTH
	*
	* @access     private
	* @var        integer
	*/
	var $_time	=	PATFORMS_RULE_AFTER_VALIDATION;

   /**
	* script that will be displayed only once
	*
	* @access     private
	* @var        array
	*/
	var $globalScript	=	array();

   /**
	* script that will be displayed once per instance
	*
	* @access     private
	* @var        array
	*/
	var $instanceScript	=	array();

   /**
	* properties that have to be replaced in the instance script.
	*
	* @access     private
	* @var        array
	*/
	var $scriptPlaceholders	=	array();

   /**
	* store the container of the rule
	*
	* @access     private
	* @var        object
	*/
	var $container;

   /**
	* define error codes an messages for each form element
	*
	* @abstract
	* @access     private
	* @var        array
	*/
	var	$validatorErrorCodes  =   array();

   /**
	* error code offset for the rule
	*
	* @abstract
	* @access     private
	*/
	var	$errorOffset;

   /**
	* format of the rule
	*
	* @abstract
	* @access     private
	*/
	var	$format	=	'html';

   /**
	* name of the rule
	*
	* @abstract
	* @access     private
	*/
	var	$ruleName = '';

   /**
	* Get the time when the rule should be applied.
	*
	* This has to be defined in the _time property of the rule.
	*
	* @access     public
	* @return     integer
	*/
	function getTime()
	{
		return $this->_time;
	}

   /**
	* create a new rule object
	*
	* @access     public
	* @param      string	id
	*/
	function patForms_Rule( $id = null )
	{
		if ( $id === null )
		{
			$id	=	uniqid( '' );
		}

		$this->_id	=	$id;
	}

   /**
	* set the id for the rule
	*
	* @access     public
	* @param      string	id
	*/
	function setId( $id )
	{
		$this->_id	=	$id;
	}

   /**
	* set the locale, this is needed to update the rule
	* translations, that have been passed to the container
	* element
	*
	* @access     public
	* @param      string		new locale
	* @return     boolean
	*/
	function setLocale( $locale )
	{
		// rules do not store locale information
		if (!patForms::isCustomLocale($locale)) {
			return true;
		}

		$errorMessages = patForms::getCustomLocale($locale, 'Rule::' . $this->getRuleName());

		if (is_array($errorMessages)) {
			$this->validatorErrorCodes[$locale] = $errorMessages;
		}

		$this->container->addValidatorErrorCodes( $this->validatorErrorCodes, $this->errorOffset );

		return true;
	}

   /**
	* prepare the rule
	*
	* This method is used to initialize the rule.
	* By default it adds it validatorErrorCodes
	* to the container and stores a reference to the
	* container.
	*
	* You may extend it in your custom rules, but should always be calling
	* this method using:
	*
	* <code>
	* patForms_Rule::prepareRule( $container );
	* </code>
	*
	* @access     public
	* @param      object	Either a patForms or patForms_Element object
	*/
	function prepareRule( &$container )
	{
		$this->format		=	$container->getFormat();

		$this->container	=	&$container;
		$this->errorOffset	=	$container->getErrorOffset();

		$container->addValidatorErrorCodes( $this->validatorErrorCodes, $this->errorOffset );

		return true;
	}

   /**
	* method called by patForms or any patForms_Element to validate the
	* element or the form.
	*
	* @abstract
	* @access     public
	* @param      object	    Either a patForms or patForms_Element object
	* @return     boolean     true, if rule has been applied succesfully, false otherwise
	*/
	function applyRule( &$container, $type = PATFORMS_RULE_BEFORE_VALIDATION )
	{
		// your code
	}

   /**
	* addValidationError
	*
	* @access     private
	* @param      integer	$code
	* @param      array	$vars	fill named placeholder with values
	* @return     boolean $result	true on success
	*/
	function addValidationError( $code, $vars = array() )
	{
		$code= $this->errorOffset + $code;
		return $this->container->addValidationError( $code, $vars );
	}

   /**
	* get the name of the rule
	*
	* By default just return the classname, this is sufficient.
	*
	* @access     public
	* @return     string
	*/
	function getRuleName()
	{
		if (!empty($this->ruleName)) {
			return $this->ruleName;
		}
		return get_class( $this );
	}

   /**
	* get the global javascript of the rule
	*
	* @access     public
	* @return     string
	* @todo       Rules need to know the output format
	*/
	/*
	function getGlobalJavascript()
	{
		if ( isset( $this->globalScript['html'] ) )
		{
			return $this->globalScript['html'];
		}
		return '';
	}
	*/

   /**
	* get the instance javascript of the rule
	*
	* @access     public
	* @return     string
	*/
	/*
	function getInstanceJavascript()
	{
		if ( !isset( $this->instanceScript[$this->format] ) )
		{
			return false;
		}
		// get the script for the current format
		$script	=	$this->instanceScript[$this->format];

		// always replace the id
		$script	=	str_replace( '[RULE::ID]', $this->_id, $script );
		if ( method_exists( $this->container, 'getId' ) )
		{
			$script	=	str_replace( '[CONTAINER::ID]', $this->container->getId(), $script );
		}
		if ( method_exists( $this->container, 'getName' ) )
		{
			$script	=	str_replace( '[CONTAINER::NAME]', $this->container->getName(), $script );
		}

		foreach ( $this->scriptPlaceholders as $placeholder => $property )
		{
			if ( isset( $this->$property ) )
				$script	=	str_replace( '['.$placeholder.']', $this->$property, $script );
			else
				$script	=	str_replace( '['.$placeholder.']', '', $script );
		}
		return $script;
	}
	*/

	function registerJavascripts(&$form) {

		if ($script = $this->getGlobalJavascript()) {
			$form->registerGlobalJavascript($this->getRuleName(), $script);
		}

		if ($script = $this->getInstanceJavascript()) {
			$form->registerInstanceJavascript($script);
		}
	}

	function getGlobalJavascript() {

		if (isset($this->globalScript[$this->format])) {
			return $this->globalScript[$this->format];
		}
	}

	function getInstanceJavascript(){

		if (isset($this->instanceScript[$this->format])) {
			$script	= $this->instanceScript[$this->format];
			$script = str_replace('[RULE::ID]', $this->_id, $script);
			if (method_exists($this->container, 'getId')) {
				$script = str_replace('[CONTAINER::ID]', $this->container->getId(), $script);
			}
			if (method_exists($this->container, 'getName')) {
				$script = str_replace('[CONTAINER::NAME]', $this->container->getName(), $script);
			}
			foreach ($this->scriptPlaceholders as $placeholder => $property) {
				if (isset($this->$property)) {
					$script = str_replace('['.$placeholder.']', $this->$property, $script);
				} else {
					$script = str_replace('['.$placeholder.']', '', $script);
				}
			}
			return $script;
		}
	}
}
