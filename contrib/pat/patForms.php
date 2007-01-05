<?php
/**
 * patForms form manager class - serialize form elements into any given output format
 * using element classes, and build the output via renderer classes.
 *
 * $Id$
 *
 * @package    patForms
 * @author     Sebastian Mordziol <argh@php-tools.net>
 * @author     gERD Schaufelberger <gerd@php-tools.net>
 * @author     Stephan Schmidt <schst@php-tools.net>
 * @copyright  2003-2004 PHP Application Tools
 * @license    LGPL
 * @link       http://www.php-tools.net
 */

/**
 * set the include path
 */
if ( !defined( 'PATFORMS_INCLUDE_PATH' ) ) {
	define( 'PATFORMS_INCLUDE_PATH', dirname( __FILE__ ). '/patForms' );
}

/**
 * needs helper methods of patForms_Element
 */
include_once PATFORMS_INCLUDE_PATH . "/Element.php";

/**
 * error definition: renderer base class file (renderers/_base.php) could not
 * be found.
 *
 * @see        patForms::_createModule()
 */
define( "PATFORMS_ERROR_NO_MODULE_BASE_FILE", 1001 );

/**
 * error definition: the specified renderer could not be found.
 *
 * @see        patForms::_createModule()
 */
define( "PATFORMS_ERROR_MODULE_NOT_FOUND", 1002 );

/**
 * error definition: the element added via the {@link patForms::addElement()}
 * is not an object. Use the {@link patForms::createElement()} method to
 * create an element object.
 *
 * @see        patForms::addElement()
 * @see        patForms::createElement()
 */
define( "PATFORMS_ERROR_ELEMENT_IS_NO_OBJECT", 1003 );

/**
 * error definition: generic unexpected error.
 */
define( "PATFORMS_ERROR_UNEXPECTED_ERROR", 1004 );

/**
 * element does not exist
 */
define( "PATFORMS_ERROR_ELEMENT_NOT_FOUND", 1012 );

/**
 * renderer object has not been set - if you want to render the form, you have to
 * set a renderer object via the {@link patForms::setRenderer()} method. To create
 * a renderer, use the {@link patForms::createRenderer()} method.
 *
 * @see        patForms::setRenderer()
 * @see        patForms::createRenderer()
 */
define( "PATFORMS_ERROR_NO_RENDERER_SET", 1013 );

/**
 * invalid renderer
 *
 * @see        createRenderer()
 */
define( "PATFORMS_ERROR_INVALID_RENDERER", 1014 );

/**
 * invalid method
 *
 * @see        setMethod()
 */
define( "PATFORMS_ERROR_INVALID_METHOD", 1015 );

/**
 * Given parameter is not a boolean value
 */
define( "PATFORMS_ERROR_PARAMETER_NO_BOOL", 1016 );

/**
 * Given Static property does not exist
 */
define( "PATFORMS_ERROR_NO_STATIC_PROPERTY", 1017 );

/**
 * Unknown event
 */
define( "PATFORMS_ERROR_UNKNOWN_EVENT", 1018 );

/**
 * Invalid event handler
 */
define( "PATFORMS_ERROR_INVALID_HANDLER", 1019 );

/**
 * Event exists
 */
define( 'PATFORMS_NOTICE_EVENT_ALREADY_REGISTERED', 1020 );

/**
 * Invalid storage container
 */
define( 'PATFORMS_ERROR_INVALID_STORAGE', 1021 );

define( 'PATFORMS_NOTICE_ARRAY_EXPECTED', 1022 );

define( 'PATFORMS_NOTICE_ATTRIBUTE_NOT_SUPPORTED', 1023 );

define( 'PATFORMS_NOTICE_INVALID_OPTION', 1024 );

define( 'PATFORMS_ERROR_ATTRIBUTE_REQUIRED', 1025 );

define( 'PATFORMS_ERROR_CAN_NOT_VERIFY_FORMAT', 1026 );

define( 'PATFORMS_ERROR_METHOD_FOR_MODE_NOT_AVAILABLE', 1027 );


/**
 * errors apply on translating errors matching current locale settings
 */
define( 'PATFORMS_NOTICE_VALIDATOR_ERROR_LOCALE_UNDEFINED', 1028 );
define( 'PATFORMS_WARNING_VALIDATOR_ERROR_UNDEFINED', 1029 );

/**
 * apply the rule before the built-in validation
 */
define( 'PATFORMS_RULE_BEFORE_VALIDATION', 1 );

/**
 * apply the rule after the built-in validation
 */
define( 'PATFORMS_RULE_AFTER_VALIDATION', 2 );

/**
 * apply the rule before AND after the built-in validation
 */
define( 'PATFORMS_RULE_BOTH', 3 );

/**
 * attach the observer to the elements
 */
define( 'PATFORMS_OBSERVER_ATTACH_TO_ELEMENTS', 1 );

/**
 * attach the observer to the form
 */
define( 'PATFORMS_OBSERVER_ATTACH_TO_FORM', 2 );

/**
 * attach the observer to the form and the elements
 */
define( 'PATFORMS_OBSERVER_ATTACH_TO_BOTH', 3 );

/**
 * group values should stay nested
 */
define('PATFORMS_VALUES_NESTED', 0);

/**
 * group values should be flattened
 */
define('PATFORMS_VALUES_FLATTENED', 1);

/**
 * group values should be prefixed
 */
define('PATFORMS_VALUES_PREFIXED', 2);

/**
 * Static patForms properties - used to emulate pre-PHP5 static properties.
 *
 * @see        setStaticProperty()
 * @see        getStaticProperty()
 */
$GLOBALS['_patForms']	=	array(
	'format'			=>	'html',
	'locale'			=>	'C',
	'customLocales'		=>	array(),
	'autoFinalize'		=>	true,
	'defaultAttributes'	=>	array(),
);

/**
 * patForms form manager class - serialize form elements into any given output format
 * using element classes, and build the output via renderer classes.
 *
 * @package    patForms
 * @author     Sebastian Mordziol <argh@php-tools.net>
 * @author     gERD Schaufelberger <gerd@php-tools.net>
 * @author     Stephan Schmidt <schst@php-tools.net>
 * @copyright  2003-2004 PHP Application Tools
 * @license    LGPL
 * @link       http://www.php-tools.net
 * @version    0.9.0alpha
 * @todo       check the clientside functionality, as that can lead to broken pages
 */
class patForms
{
   /**
	* javascript that will displayed only once
	*
	* @access     private
	* @var        array
	*/
	var $globalJavascript	=	array();

   /**
	* javascript that will be displayed once per instance
	*
	* @access     private
	* @var        array
	*/
	var $instanceJavascript	=	array();

   /**
	* stores the mode for the form. It defaults to 'default', and is only overwritten if
	* set specifically. It is passed on to any elements you create.
	*
	* @access     private
	* @see        setMode()
	*/
	var $mode	=	'default';

   /**
	* XML entities
	*
	* @access     private
	* @see        toXML()
	* @todo       This is redundant to the Element's xmlEntities property - find a way to keep this in one place
	*/
	var $xmlEntities = array(
		"<"	=>	"&lt;",
		">"	=>	"&gt;",
		"&"	=>	"&amp;",
		"'"	=>	"&apos;",
		'"'	=>	"&quot;"
	);

   /**
	* stores the format for the element. It defaults to 'html', and is only overwritten if
	* set specifically. It is passed on to any elements you create.
	*
	* @access     private
	* @see        setFormat()
	*/
	var $format	=	'html';

   /**
	* stores the flag telling the form whether it has been submitted - this is passed on to any
	* elements you create.
	*
	* @access     private
	* @see        setSubmitted()
	*/
	var $submitted	=	false;

   /**
	* stores the element objects of this form.
	* @access     private
	* @see        addElement()
	*/
	var $elements	=	array();

   /**
	* stores the current element count for this form, used to generate the ids for each element
	* @access     private
	* @see        getElementId()
	*/
	var $elementCounter	=	0;

   /**
	* stores a renderer
	* @access     private
	* @see        setRenderer(), renderForm()
	*/
	var $renderer		=	null;

   /**
	* stores the locale to use when adding validation errors for the whole form.
	*
	* @access     private
	* @var        string	$locale
	* @see        setLocale()
	*/
	var	$locale		=	'C';

   /**
	* stores custom locale
	*
	* @access     private
	* @var        array
	* @see        setLocale()
	*/
	var	$customLocales = array();

   /**
	* stores the element name
	* @access     private
	* @see        getElementName()
	*/
	var $elementName = 'Form';

   /**
	* flag to indicate, whether form should be validated automatically
	* by renderForm()
	*
	* @access     private
	* @var        string
	* @see        setAutoValidate(), renderForm()
	*/
	var	$autoValidate	=	false;

   /**
	* name of the variable that indicates, whether the form has
	* been submitted.
	*
	* @access     private
	* @var        string
	* @see        setAutoValidate()
	*/
	var	$submitVar	=	null;

   /**
	* event handlers
	*
	* @access     private
	* @var        array
	* @see        registerEventHandler()
	* @see        registerEvent()
	*/
	var	$_eventHandler	=	array();

   /**
	* events that can be triggered
	*
	* @access     private
	* @var        array
	* @see        registerEventHandler()
	* @see        triggerEvent()
	* @see        registerEvent()
	*/
	var	$_validEvents	=	array( 'onInit', 'onValidate', 'onSubmit', 'onError', 'onSuccess' );

   /**
	* Stores whether the current form has been validated
	*
	* @access     private
	*/
	var $validated	=	false;

   /**
	* Stores whether the current form is valid or not (after the
	* validation process)
	*
	* @access     private
	*/
	var $valid	=	null;

   /**
	* Stores the names of all static properties that patForms will use as defaults
	* for the properties with the same name on startup.
	*
	* @access     private
	*/
	var $staticProperties	=	array(
		'format'		=>	'setFormat',
		'autoFinalize'	=>	'setAutoFinalize',
		'locale'		=>	'setLocale',
	);

   /**
	* Stores the flag for the autoFinalize feature
	*
	* @access     private
	*/
	var $autoFinalize	=	true;

   /**
	* custom validation rules
	*
	* @access     private
	* @var        array
	*/
	var $_rules			=	array();

   /**
	* define error codes an messages for the form
	*
	* Will be set by validation rules that have been
	* added to the form.
	*
	* @access     private
	* @var        array	$validatorErrorCodes
	*/
	var	$validatorErrorCodes  =   array();

   /**
	* stores any validation errors that can occurr during the
	* form's validation process.
	*
	* @access     private
	* @var        array	$validationErrors
	*/
	var	$validationErrors  =   array();

   /**
	* next error offset for rules
	* @access     private
	* @var        integer
	*/
	var $nextErrorOffset	=	1000;

   /**
	* Attributes of the form - needed to generate the form tag
	*
	* @access     private
	* @var        array	$attributes
	* @see        setAttribute()
	*/
	var	$attributes	=	array();

   /**
	* Attribute definition for the form - defines which attribute the form
	* itself supports.
	*
	* @access     public
	*/
	var	$attributeDefinition	=	array(

		'id' =>	array(
			'required'		=>	false,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),

		'name' => array(
			'required'		=>	true,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),

		'method' => array(
			'required'		=>	true,
			'format'		=>	'string',
			'default'		=>	'post',
			'outputFormats'	=>	array( 'html' ),
		),

		'action' => array(
			'required'		=>	true,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),

		'accept' => array(
			'required'		=>	false,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),

		'accept-charset' => array(
			'required'		=>	false,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),

		'enctype' => array(
			'required'		=>	false,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),

		'onreset' => array(
			'required'		=>	false,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),

		'onsubmit' => array(
			'required'		=>	false,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),

		'target' => array(
			'required'		=>	false,
			'format'		=>	'string',
			'outputFormats'	=>	array( 'html' ),
		),
	);

   /**
	* Stores all available patForms options - these are inherited by all elements
	* and their dependencies, like rules.
	*
	* Short option overview:
	*
	* - scripts: enable client script integration
	*
	* @access     public
	*/
	var $options	=	array(

		'scripts'	=>	array(
			'enabled'	=>	true,
			'params'	=>	array(),
		),

	);

   /**
	* observers of the form
	*
	* @access     private
	* @var        array
	*/
	var	$observers = array();

   /**
	* Sets the default attributes that will be inherited by any elements you add to the form.
	*
	* <b>Note:</b> You have to call this method statically before creating a new form if you use
	* patForm's automatic element creation feature via the {@link createForm()} method, as the
	* default attributes cannot be set after an element has been created.
	*
	* @static
	* @access     public
	* @param      array	$attributes	The list of attributes to set with key => value pairs.
	*/
	function setDefaultAttributes( $attributes )
	{
		patForms::setStaticProperty( 'defaultAttributes', $attributes );
	}

   /**
	* sets the locale (language) to use for the validation error messages of all elements
	* in the form.
	*
	* @access     public
	* @param      string		language code
	* @param      string		optional language file
	* @return     bool		True on success
	*/
	function setLocale( $locale, $languageFile = null )
	{
		if (!is_null($languageFile)) {
			$languageData   = patForms::parseLocaleFile($languageFile);

			$customLocales = patForms::getStaticProperty('customLocales');
			$customLocales[$locale] = $languageData;
			patForms::setStaticProperty('customLocales', $customLocales);
		}

		if ( isset( $this ) && is_a( $this, 'patForms' ) ) {
			$this->locale = $locale;

			if ( !empty( $this->elements ) ) {
				$cnt	=	count( $this->elements );
				for ( $i=0; $i < $cnt; $i++ ) {
					$this->elements[$i]->setLocale( $locale );
				}
			}
		} else {
			patForms::setStaticProperty('locale', $locale);
		}

		return true;
	}

   /**
	* checks, whether a locale is a custom locale
	*
	* @static
	* @access     public
	* @param      string		locale name
	* @return     boolean
	*/
	function isCustomLocale($locale)
	{
		$customLocales = patForms::getStaticProperty('customLocales');
		if (isset($customLocales[$locale])) {
			return true;
		}
		return false;
	}

   /**
	* get the custom locale for an element or a rule
	*
	* @static
	* @access     public
	* @param      string		locale
	* @param      string		key
	* @return     array
	*/
	function getCustomLocale($locale, $key)
	{
		$customLocales = patForms::getStaticProperty('customLocales');
		if (!isset($customLocales[$locale])) {
			return false;
		}
		if (!isset($customLocales[$locale][$key])) {
			return false;
		}
		return $customLocales[$locale][$key];
	}

   /**
	* parses a locale file
	*
	* @access     private
	* @param      string		filename
	* @return     array		locale information
	* @todo       add some file checks
	*/
	function parseLocaleFile($filename)
	{
		return parse_ini_file($filename, true);
	}

   /**
	* sets the format of the element - this will be passed on to any elements you create. If you
	* have already added some elements when you call this method, it will be passed on to them too.
	*
	* @access     public
	* @param      string	$format	The name of the format you have implemented in your element(s).
	* @return     bool	$result	True on success
	* @see        setMode()
	* @see        format
	* @see        serialize()
	*/
	function setFormat( $format )
	{
		if ( isset( $this ) && is_a( $this, 'patForms' ) )
		{
			$this->format	=	strtolower( $format );

			if ( !empty( $this->elements ) )
			{
				$cnt	=	count( $this->elements );
				for ( $i=0; $i < $cnt; $i++ )
				{
					$this->elements[$i]->setFormat( $format );
				}
			}
		}
		else
		{
			patForms::setStaticProperty( 'format', $format );
		}

		return true;
	}

   /**
	* sets the mode of the form - If you have already added some elements when you call this
	* method, it will be passed on to them too.
	*
	* @access     public
	* @param      string	$mode	The mode to set the form to: default|readonly or any other mode you have implemented in your element class(es). Default is 'default'.
	* @see        setMode()
	* @see        mode
	* @see        serialize()
	*/
	function setMode( $mode )
	{
		$this->mode	=	strtolower( $mode );

		if ( !empty( $this->elements ) )
		{
			$cnt	=	count( $this->elements );
			for ( $i=0; $i < $cnt; $i++ )
			{
				$this->elements[$i]->setMode( $mode );
			}
		}
	}

   /**
	* sets the current submitted state of the form. Set this to true if you want the form
	* to pick up its submitted data. It will pass on this information to all elements that
	* have been added so far, and new ones inherit it too.
	*
	* @access     public
	* @param      bool	$state	True if it has been submitted, false otherwise (default).
	* @see        isSubmitted()
	* @see        submitted
	*/
	function setSubmitted( $state )
	{
		if ( $state == true )
		{
			$eventState	=	$this->triggerEvent( 'Submit' );
			if ( $eventState === false )
				return	false;
		}

		$this->submitted	=	$state;

		if ( !empty( $this->elements ) )
		{
			$cnt	=	count( $this->elements );
			for ( $i=0; $i < $cnt; $i++ )
			{
				$this->elements[$i]->setSubmitted( $state );
			}
		}

		return $state;
	}

   /**
	* sets the method for the request
	*
	* @access     public
	* @param      string	$method		GET or POST
	* @see        method
	* @uses       setAttribute()
	*/
	function setMethod( $method )
	{
		$method	=	strtolower( $method );

		if ( $method != 'get' && $method != 'post' )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_INVALID_METHOD,
				'Unknown method "'.$method.'". Currently only GET and POST are supported as patForms methods.'
			);
		}
		$this->setAttribute( 'method', $method );
		return	true;
	}

   /**
	* sets the action for the form
	*
	* This is a only a wrapper for setAttribute()
	*
	* @access     public
	* @param      string	$action
	* @see        setAttribute()
	*/
	function setAction( $action )
	{
		return $this->setAttribute( 'action', $action );
	}

   /**
	* Sets the AutoFinalize mode for the form. The AutoFinalize mode will tell patForms to
	* finalize all elements after the form has been validated successfully.
	*
	* @access     public
	* @param      boolean	$mode		Whether to activate the AutoFinalize mode (true) or not (false).
	* @return     boolean	$success	True if okay, a patError object otherwise.
	* @see        finalizeForm()
	*/
	function setAutoFinalize( $mode )
	{
		if ( !is_bool( $mode ) )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_PARAMETER_NO_BOOL,
				'The setAutoFinalize() method requires a boolean value ( true or false ) as parameter.'
			);
		}

		if ( isset( $this ) && is_a( $this, 'patForms' ) )
		{
			$this->autoFinalize	=	$mode;
		}
		else
		{
			patForms::setStaticProperty( 'autoFinalize', $mode );
		}

		return true;
	}

   /**
	* Wrapper method that adds a filter to all elements
	* of the form at once instead of having to do it for
	* each element.
	*
	* @access     public
	* @param      object	&$filter	The filter object to apply
	* @see        patForms_Element::applyFilter()
	* @todo       add error management and docs once the element's applyFilter method has too
	*/
	function applyFilter( &$filter )
	{
		if ( empty( $this->elements ) )
			return true;

		$cnt = count( $this->elements );

		for ( $i = 0; $i < $cnt; $i++ )
		{
			$this->elements[$i]->applyFilter( $filter );
		}
	}

   /**
	* creates a new patForms object and returns it; this method is made to be called statically
	* to be able to create a new patForms object from anywhere.
	*
	* @access     public
	* @param      array	$formDefinition		Optional form definition for elements that will be added to the form
	* @param      array	$attributes			The attributes to set for the form itself
	* @return     object patForms	$form	The new patForms object.
	* @todo       it should be possible to pass Rule definitions, so they can be loaded and added	automatically.
	*/
	function &createForm( $formDefinition = null, $attributes = null )
	{
		$form	=	&new patForms();

		if ( $attributes != null )
		{
			$form->setAttributes( $attributes );
		}

		if ( $formDefinition === null )
			return	$form;

		foreach ( $formDefinition as $name => $element )
		{
			if ( !isset( $element["filters"] ) )
			{
				$element["filters"]	=	null;
			}
			if ( !isset( $element["children"] ) )
			{
				$element["children"]	=	null;
			}

			$el	= &$form->createElement( $name, $element["type"], $element["attributes"], $element["filters"], $element["children"] );

			if ( isset( $element["renderer"] ) ) {
				$el->setRenderer( $element["renderer"] );
			}

			$result		=	$form->addElement( $el );
			if (patErrorManager::isError( $result )) {
				return	$result;
			}
		}
		return $form;
	}

   /**
	* add a custom validation rule
	*
	* @access     public
	* @param      object patForms_Rule	validation rule
	* @param      integer					time to apply rule (before or after built-in validation)
	* @param      boolean					apply the rule, even if the form is invalid
	* @param      boolean					should form get revalidated (not implemented yet)
	* @return     boolean					currently always true
	*/
	function addRule( &$rule, $time = PATFORMS_RULE_AFTER_VALIDATION, $invalid = false, $revalidate = false )
	{
		$rule->prepareRule( $this );

		$this->_rules[]	=	 array(
									'rule'			=>	&$rule,
									'time'			=>	$time,
									'invalid'		=>	$invalid,
									'revalidate'	=>	$revalidate
								 );
	}

   /**
	* patForms PHP5 constructor - processes some intitialization tasks like merging the currently
	* set static properties with the internal properties.
	*
	* @access     public
	*/
	function __construct()
	{
		foreach ( $this->staticProperties as $staticProperty => $setMethod )
		{
			$propValue	=	patForms::getStaticProperty( $staticProperty );
			if ( patErrorManager::isError( $propValue ) )
				continue;

			$this->$setMethod( $propValue );
		}

		// initialize patForms internal attribute collection
		$this->loadAttributeDefaults();
	}

   /**
	* patForms pre-PHP5 constructor - does nothing for the moment except being a wrapper
	* for the PHP5 contructor for older PHP versions support.
	*
	* @access     public
	*/
	function patForms()
	{
		patForms::__construct();
	}

   /**
	* sets a renderer object that will be used to render
	* the form.
	*
	* @access     public
	* @param      object		&$renderer	The renderer object
	* @return     mixed		$success	True on success, patError object otherwise.
	* @see        createRenderer()
	* @see        renderForm()
	*/
	function setRenderer( &$renderer, $args = array() )
	{
		if ( !is_object( $renderer ) )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_INVALID_RENDERER,
				'You can only set a patForms_Renderer object with the setRenderer() method, "'.gettype( $renderer ).'" given.'
			);
		}

		$this->renderer	=	&$renderer;

		if ( isset( $args['includeElements'] ) && $args['includeElements'] === true )
		{
			// check all elements - there may be some that need
			// renderers too, so we give them the same renderer if
			// they don't already have one.
			$cnt = count( $this->elements );
			for ( $i = 0; $i < $cnt; $i++ )
			{
				if ( $this->elements[$i]->usesRenderer && !is_object( $this->elements[$i]->renderer ) )
				{
					$this->elements[$i]->setRenderer( $renderer );
				}
			}
		}

		return true;
	}

   /**
	* sets a storage container object that will be used to store data
	*
	* @access     public
	* @param      object patForms_Storage
	* @see        createStorage()
	*/
	function setStorage( &$storage )
	{
		if ( !is_object( $storage ) )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_INVALID_STORAGE,
				'You can only set a patForms_Storage object with the setStorage() method, "'.gettype( $storage ).'" given.'
			);
		}

		$this->registerEventHandlerObject( $storage,
											array(
													'onInit'	=>	'loadEntry',
													'onValidate' =>	'validateEntry',
													'onSuccess'	=>	'storeEntry'
												)
										);
	}

   /**
	* renders the form with the renderer that was set via the {@link setRenderer()}
	* method.
	*
	* WARNING: This is still in alpha state!
	*
	* Should this method return a reference??
	* The return value could contain large blocks of HTML or large arrays!
	* Do we want to copy these?
	*
	* @access     public
	* @param      mixed	$args		arguments that will be passed to the renderer
	* @return     mixed	$form		The rendered form, or false if failed.
	*/
	function renderForm( $args = null )
	{
		if ( $this->renderer === null )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_NO_RENDERER_SET,
				'Form cannot be rendered, you have to set a renderer first via the setRenderer() method.'
			);
		}

		// form is not submitted, or auto-validation is disabled => render it
		if ( !$this->isSubmitted() || $this->autoValidate !== true )
		{
			$this->triggerEvent( 'Init' );
			return $this->renderer->render( $this, $args );
		}

		$this->validateForm();

		return $this->renderer->render( $this, $args );
	}

   /**
	* Validates all elements of the form.
	*
	* @access     public
	* @param      boolean     Flag to indicate, whether form should be validated again, if it already has been validated.
	* @return     boolean	    True if all elements could be validated, false otherwise.
	* @see        finishForm()
	*/
	function validateForm( $revalidate = false )
	{
		if ( $this->validated && !$revalidate )
			return $this->valid;

		$valid	=	true;

		/**
		 * validate custom rules
		 */
		if ( !$this->_applyRules( PATFORMS_RULE_BEFORE_VALIDATION ) )
		{
			$valid	=	false;
		}

		/**
		 * validate elements
		 */
		if ( $valid === true )
		{
			$cnt	=	count( $this->elements );
			for ( $i = 0; $i < $cnt; ++$i )
			{
				if ( !$this->elements[$i]->validate() )
				{
					$valid	=	false;
				}
			}
		}

		if ($valid === true) {
			$result = $this->triggerEvent('Validate');
			if ($result === false) {
				$valid = false;
			}
		}

		/**
		 * validate custom rules
		 */
		if ( !$this->_applyRules( PATFORMS_RULE_AFTER_VALIDATION, $valid ) )
		{
			$valid	=	false;
		}

		if ( $valid === true && $this->autoFinalize === true )
			$this->finalizeForm();

		$this->valid	=	$valid;

		$this->validated = true;

		if ( $valid === true )
		{
			$this->_announce( 'status', 'validated' );
			$event	=	'Success';
		}
		else
		{
			$this->_announce( 'status', 'error' );
			$event	=	'Error';
		}

		$this->triggerEvent( $event );

		return $this->valid;
	}

   /**
	* apply rules
	*
	* @access     private
	* @param      integer		time of validation
	* @param      boolean		form is valid
	* @return     boolean		rules are valid or not
	* @todo       add documentation
	*/
	function _applyRules( $time, $isValid = true )
	{
		$valid = true;

		$cnt = count( $this->_rules );
		for ($i = 0; $i < $cnt; $i++) {

			// wrong time
			if (( $this->_rules[$i]['time'] & $time ) != $time) {
				continue;
			}
			if (!$isValid && !$this->_rules[$i]['invalid']) {
				continue;
			}

			$result	=	$this->_rules[$i]['rule']->applyRule( $this, PATFORMS_RULE_AFTER_VALIDATION );
			if ( $result === false ) {
				$valid	=	false;
			}
		}
		return	$valid;
	}

   /**
	* Finalizes the form by telling each fom element to finalize - finalizing means to
	* process any tasks that need to be done after the form has been validated, like
	* deleting any temporary files or whatever an element needs to do at that point.
	*
	* @access     public
	* @return     bool	$success	Wether all elements could be finalized
	* @see        validateForm()
	*/
	function finalizeForm()
	{
		$success	=	true;

		$cnt	=	count( $this->elements );
		for ( $i = 0; $i < $cnt; ++$i )
		{
			if ( !$this->elements[$i]->finalize() )
			{
				patErrorManager::raiseWarning(
					PATFORMS_ERROR_ELEMENT_NOT_FINALIZED,
					'Element "'.$this->elements[$i]->elementName.'" could not be finalized. See the element error messages for more details.'
				);

				$success	=	false;
			}
		}

		return $success;
	}

   /**
	* creates a new renderer from the patForms renderer collection and returns it.
	*
	* @access     public
	* @param      string						The name of the renderer to create - have a look at the Renderer/ subfolder for a list of available renderers.
	* @return     object patForms_Renderer	The renderer object, or error object
	*/
	function &createRenderer( $name )
	{
		return	patForms::_createModule( 'Renderer', $name );
	}

   /**
	* creates a new storage container and returns it.
	*
	* @access     public
	* @param      string						The name of the storage to create - have a look at the Storage/ subfolder for a list of available storage containers.
	* @return     object patForms_Storage		The storage container, or error object
	*/
	function &createStorage( $name )
	{
		return	patForms::_createModule( 'Storage', $name );
	}

   /**
	* Creates a new filter and returns it.
	*
	* You may pass an array as second parameter that contains
	* parameters for the filter. patForms will check for setter methods
	* for all keys and set the corresponding values.
	*
	* This eases the creating of simple filter objects.
	*
	* @access     public
	* @param      string						The name of the filter to create - have a look at the Filter/ subfolder for a list of available filters.
	* @param      array						Optional parameters for the filter, if you provide a parameter, make sure the filter implements a set[Paramname]() method.
	*										This will be automated with interceptors in the PHP5 version of patForms
	* @return     object patForms_Filter		The filter, or error object
	*/
	function &createFilter( $name, $params = null )
	{
		$filter	=	&patForms::_createModule( 'Filter', $name );

		if ( !is_array( $params ) )
		{
			return	$filter;
		}

		foreach ( $params as $param => $value )
		{
			$setter		=	'set' . ucfirst( $param );
			if ( method_exists( $filter, $setter ) )
			{
				$filter->$setter( $value );
			}
		}
		return	$filter;
	}

   /**
	* creates a new rule from the patForms rule collection and returns it.
	*
	* If your rules are not located in patForms/Rule you have to load and
	* instantiate them on your own.
	*
	* @access     public
	* @param      string					The name of the rule to create - have a look at the Rule/ subfolder for a list of available rules.
	* @param      string					The id of the rule, needed if the rule uses client side actions.
	* @return     object patForms_Rule	The rule object, or error object
	*/
	function &createRule( $name, $id = null )
	{
		$rule	=	&patForms::_createModule( 'Rule', $name );
		if ( $id != null )
		{
			$rule->setId( $id );
		}
		return $rule;
	}

   /**
	* creates a new observer from the patForms observer collection and returns it.
	*
	* If your observers are not located in patForms/Observer you have to load and
	* instantiate them on your own.
	*
	* @access     public
	* @param      string						The name of the observer to create - have a look at the Observer/ subfolder for a list of available observers.
	* @return     object patForms_Observer	The observer object, or error object
	*/
	function &createObserver( $name )
	{
		$observer = &patForms::_createModule( 'Observer', $name );

		return $observer;
	}

   /**
	* creates a new module for patForms
	*
	* @access     private
	* @param      string	$type		type of the module. Possible values are 'Renderer', 'Rule'
	* @param      string	$name		The name of the renderer to create - have a look at the renderers/ subfolder for a list of available renderers.
	* @return     object	$module		The module object, or an error object
	*/
	function &_createModule( $type, $name )
	{
		$baseFile		=	PATFORMS_INCLUDE_PATH . '/'.$type.'.php';
		$baseClass	=	'patForms_'.$type;

		// if there is an underscore in the module name, we want
		// to load the module from a subfolder, so we transform
		// all underscores to slashes.
		$pathName	=	$name;
		if ( strstr( $pathName, '_' ) )
		{
			$pathName	=	str_replace( '_', '/', $name );
		}

		$moduleFile		=	PATFORMS_INCLUDE_PATH . '/'.$type.'/'.$pathName.'.php';
		$moduleClass	=	'patForms_'.$type.'_'.$name;

		if ( !class_exists( $baseClass ) )
		{
			if ( !file_exists( $baseFile ) )
			{
				return patErrorManager::raiseError(
					PATFORMS_ERROR_NO_MODULE_BASE_FILE,
					$type .' base file could not be found',
					'Tried to load base file in path "'.$baseFile.'"'
				);
			}

			include_once $baseFile;
		}

		if ( !class_exists( $moduleClass ) )
		{
			if ( !file_exists( $moduleFile ) )
			{
				return patErrorManager::raiseError(
					PATFORMS_ERROR_MODULE_NOT_FOUND,
					$type.' "'.$name.'" file "'.$moduleFile. '" could not be found.'
				);
			}

			include_once $moduleFile;
		}

		$module	=	&new $moduleClass();

		return $module;
	}

   /**
	* adds an element to the form - has to be a patForms_Element object. Use the {@link createElement()}
	* method to create a new element object. Also takes care of passing on the form's configuration
	* including the mode, format and submitted flags to the element.
	*
	* @access     public
	* @param      object	&$element	The patForms_Element object to add to this form.
	* @return     bool	$success	True if everything went well, false otherwise.
	* @see        patForms_Element
	* @see        createElement()
	*/
	function addElement( &$element )
	{
		if ( !is_object( $element ) )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_ELEMENT_IS_NO_OBJECT,
				'The addElement() method expects an element object, "'.gettype( $element ).'" given.'
			);
		}

		if ( patErrorManager::isError( $element ) )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_UNEXPECTED_ERROR,
				'The element you are trying to add is a patError object, and not a patForms element object.'
			);
		}

		if ( !$element->getId() ) {
			$element->setId( $this->getElementId() );
		}
		$element->setMode( $this->getMode() );
		$element->setFormat( $this->getFormat() );
		$element->setSubmitted( $this->isSubmitted() );
		$element->setLocale( $this->getLocale() );

		$this->elements[]	=&	$element;

		return true;
	}

   /**
	* replaces an element in the form
	*
	* @access     public
	* @param      object	$element	The patForms_Element object to be replaced
	* @param      object	&$replace	The element that will replace the old element
	* @return     bool	$success	True if everything went well, false otherwise.
	* @see        patForms_Element
	* @see        addElement()
	*/
	function replaceElement( $element, &$replace )
	{
		if ( !is_object( $replace ) ) {
			return patErrorManager::raiseError(
				PATFORMS_ERROR_ELEMENT_IS_NO_OBJECT,
				'The addElement() method expects an element object, "'.gettype( $replace ).'" given.'
			);
		}

		if ( patErrorManager::isError( $replace ) ) {
			return patErrorManager::raiseError(
				PATFORMS_ERROR_UNEXPECTED_ERROR,
				'The element you are trying to add is a patError object, and not a patForms element object.'
			);
		}

		if (is_object($element)) {
			$element = $element->getId();
		}

		$cnt = count($this->elements);
		for ($i = 0; $i < $cnt; $i++) {
			if ($this->elements[$i]->getId() === $element) {

				if ( !$replace->getId() ) {
					$replace->setId( $this->getElementId() );
				}
				$replace->setMode( $this->getMode() );
				$replace->setFormat( $this->getFormat() );
				$replace->setSubmitted( $this->isSubmitted() );
				$replace->setLocale( $this->getLocale() );

				$this->elements[$i] = &$replace;
				return true;
			}

			// the current element is a container
			if (method_exists($this->elements[$i], 'replaceElement')) {
				$result = $this->elements[$i]->replaceElement($element, $replace);
				if ($result === true) {
					return $result;
				}
			}
		}

		return false;
	}

   /**
	* Get an element by its name.
	*
	* @access     public
	* @param      string	$name	name of the element
	* @return     object			patForms element
	* @deprecated please use patForms::getElementByName() instead
	*/
	function &getElement( $name )
	{
		return $this->getElementByName( $name );
	}

   /**
	* Get an element by its name.
	*
	* @access     public
	* @param      string	$name	name of the element
	* @return     mixed			either a patForms element or an array containing patForms elements
	* @see        getElementById()
	*/
	function &getElementByName( $name )
	{
		if ( $name == '__form' ) {
			return $this;
		}

		$elements = array();
		$cnt      = count( $this->elements );
		for ($i = 0; $i < $cnt; $i++) {
			if ($this->elements[$i]->getName() == $name) {
				$elements[]	= &$this->elements[$i];
				continue;
			}
			if (method_exists($this->elements[$i], 'getElementById')) {
				patErrorManager::pushExpect(PATFORMS_ERROR_ELEMENT_NOT_FOUND);
				$result = &$this->elements[$i]->getElementByName($name);
				patErrorManager::popExpect();
				if (!patErrorManager::isError($result)) {
					if (is_array($result)) {
						$cnt2 = count( $result );
						for ($j = 0; $j < $cnt2; $j++) {
							$elements[]	= &$result[$j];
						}
					} else {
						$elements[]	= &$result;
					}
				}
			}
		}

		switch( count( $elements ) )
		{
			case	0:
				return patErrorManager::raiseError(
					PATFORMS_ERROR_ELEMENT_NOT_FOUND,
					'Element '.$name.' could not be found.'
				);
				break;
			case	1:
				return	$elements[0];
				break;
			default:
				return	$elements;
				break;
		}
	}

   /**
	* Get an element by its id.
	*
	* @access     public
	* @param      string	$id		id of the element
	* @return     object			patForms element
	*/
	function &getElementById( $id )
	{
		$cnt	=	count( $this->elements );
		for ( $i = 0; $i < $cnt; $i++ )
		{
			if ( $this->elements[$i]->getId() == $id ) {
				return $this->elements[$i];
			}
			if (method_exists($this->elements[$i], 'getElementById')) {
				patErrorManager::pushExpect(PATFORMS_ERROR_ELEMENT_NOT_FOUND);
				$result = &$this->elements[$i]->getElementById($id);
				patErrorManager::popExpect();
				if (!patErrorManager::isError($result)) {
					return $result;
				}
			}
		}
		return patErrorManager::raiseError(
			PATFORMS_ERROR_ELEMENT_NOT_FOUND,
			'Element '.$name.' could not be found.'
		);
	}

   /**
	* Get all elements of the form
	*
	* @access     public
	* @return     array	all elements of the form
	*/
	function &getElements()
	{
		return	$this->elements;
	}

   /**
	* Creates a new form element and returns a reference to it.
	*
	* The optional $filters array has to be in the following format:
	*
	* <pre>
	* array(
	*       array(
	*              'filter' => 'Multiplier',
	*              'params' => array( 'multiplier' => 6 )
	*            )
	*	   )
	* </pre>
	*
	* @access     public
	* @param      string	$name		The name of the element
	* @param      string	$type		The type of the element; for a list of possible elements, have a look at the elements/ subfolder of the patForms package.
	* @param      array	$attributes	Attributes for the element
	* @param      array	$filters	Optional filters that will be applied
	* @return     object patForms_Element	$element	The element object, or patError if failed.
	*/
	function &createElement( $name, $type, $attributes, $filters = null, $children = null )
	{
		$element =& patForms::_createModule( 'Element', $type );
		if ( patErrorManager::isError( $element ) )
		{
			return	$element;
		}

		$attributes['name']	=	$name;
		if ( !isset( $attributes['id'] ) ) {
			$attributes['id'] = $this->getElementId();
		}

		// add default attributes - do this the 'silent' way be checking whether
		// the element supports the given attribute, as the element throws a notice
		// if it does not support it - this is not expected from default attributes.
		foreach ( patForms::getStaticProperty( 'defaultAttributes' ) as $attributeName => $attributeValue )
		{
			if ( !$element->hasAttribute( $attributeName ) )
			{
				continue;
			}

			$element->setAttribute( $attributeName, $attributeValue );
		}

		// set the given attributes normally
		$success = $element->setAttributes( $attributes );
		if ( patErrorManager::isError( $success ) )
		{
			return $success;
		}

		if (is_array($children)) {
			foreach ($children as $child) {
				$childName = $child['attributes']['name'];

				$childEl = &patForms::createElement($childName, $child['type'], $child['attributes']);
				if ( isset( $child["renderer"] ) ) {
					$childEl->setRenderer( $child["renderer"] );
				}

				$element->addElement($childEl);
			}
		}

		$success = $element->_init();
		if ( patErrorManager::isError( $success ) ) {
			return $success;
		}

		// if we don't have any filters to add, we're done
		if ( !is_array( $filters ) )
		{
			return $element;
		}

		$cnt	=	count( $filters );
		for ( $i = 0; $i < $cnt; $i++ )
		{
			$params =	isset( $filters[$i]['params'] ) ? $filters[$i]['params'] : null;
			$filter	=	&patForms::createFilter( $filters[$i]['filter'], $params );
			if ( patErrorManager::isError( $filter ) )
			{
				continue;
			}
			$element->applyFilter( $filter );
		}

		return $element;
	}

   /**
	* retrieves the validation errors from all elements in the form. Use this if the validateForm()
	* method returned false.
	*
	* @access     public
	* q
	* @return     array	$errors	Array containing an array with validation errors for each element in the form.
	* @todo       replace	__form with the name of the form, once attributes are implemented
	*/
	function getValidationErrors($withElements = true)
	{
		$found	=	false;
		$errors	=	array();

		if ( !empty( $this->validationErrors ) )
		{
			$errors['__form']	=	$this->validationErrors;
			$found = true;
		}

		if ($withElements === false) {
			return $errors;
		}

		$cnt = count( $this->elements );
		for ( $i = 0; $i < $cnt; ++$i )
		{
			$name	=	$this->elements[$i]->getAttribute( 'name' );
			if ( $name === false )
			{
				continue;
			}

			$elementErrors = $this->elements[$i]->getValidationErrors();

			if ( empty( $elementErrors ) )
				continue;

			$errors[$name]	=	$elementErrors;
			$found = true;
		}

		if ( $found )
			return $errors;

		return false;
	}

   /**
	* retrieves the values for all elements in the form.
	*
	* @access     public
	* @param      array		desired fields
	* @param      integer		Mode that should be used to return values in groups
	* @return     array		The values for all elements, as elementname => elementvalue.
	*
	* @todo       remove the ugly Group check and replace with something better
	* @todo       implement something similar for getValidation errors
	*/
	function getValues( $fields = null, $type = PATFORMS_VALUES_NESTED )
	{
		$values	=	array();

		$cnt = count( $this->elements );
		for ( $i = 0; $i < $cnt; ++$i )
		{
			$name	=	$this->elements[$i]->getAttribute( 'name' );
			if ( $name === false ) {
				continue;
			}

			if ( is_array( $fields ) && !in_array( $name, $fields ) ) {
				continue;
			}

			$tmpVal = $this->elements[$i]->getValue();
			if (!is_array($tmpVal) || $this->elements[$i]->elementName != 'Group') {
				$values[$name] = $tmpVal;
				continue;
			}

			switch ($type) {
				case PATFORMS_VALUES_FLATTENED:
					$values = array_merge($values, $tmpVal);
					break;
				case PATFORMS_VALUES_PREFIXED:
					foreach ($tmpVal as $key => $val) {
						$values[$name.'_'.$key] = $val;
					}
					break;
				case PATFORMS_VALUES_NESTED:
				default:
					$values[$name] = $tmpVal;
					break;

			}
		}
		return $values;
	}

   /**
	* sets the values for all elements in the form. Use this to fill your form with external
	* data, like a db query. Caution: if you do this and set the form to submitted, the values
	* will be overwritten by any values present in the $_GET or $_POST variables.
	*
	* @access     public
	* @param      array	$values	The values for all elements, as elementname => elementvalue.
	*/
	function setValues( $values, $overrideUserInput = false )
	{
		patErrorManager::pushExpect(PATFORMS_ERROR_ELEMENT_NOT_FOUND);
		foreach ($values as $elName => $value) {
			$el = &$this->getElementByName($elName);
			if (patErrorManager::isError($el)) {
				continue;
			}
			if ($overrideUserInput === true) {
				$el->setValue($value);
			} else {
				$el->setDefaultValue($value);
			}
		}
		patErrorManager::popExpect();
		return true;
	}

   /**
	* retrieves the current mode of the form
	*
	* @access     public
	* @return     string	$mode	The current form mode
	* @see        setMode()
	* @see        $mode
	*/
	function getMode()
	{
		return $this->mode;
	}

   /**
	* returns the locale that is currently set for the form.
	*
	* @access     public
	* @return     string	$locale	The locale.
	* @see        setLocale()
	* @see        $locale
	*/
	function getLocale()
	{
		return $this->locale;
	}

   /**
	* retrieves the current format of the form
	*
	* @access     public
	* @return     string	$format	The current form format
	* @see        setFormat()
	* @see        format
	*/
	function getFormat()
	{
		return $this->format;
	}

   /**
	* retrieves the current method of the form
	*
	* @access     public
	* @return     string	$method	The request method
	* @see        setMethod()
	*/
	function getMethod()
	{
		return $this->getAttribute( 'method' );
	}

   /**
	* retrieves the current action of the form
	*
	* @access     public
	* @return     string	$action		Action of the form
	* @see        setAction()
	*/
	function getAction()
	{
		$action = $this->getAttribute( 'action' );
		if ( !empty( $action ) )
			return $action;
		return	$_SERVER['PHP_SELF'];
	}

   /**
	* adds an atribute to the form's attribute collection. If the attribute
	* already exists, it is overwritten.
	*
	* @access     public
	* @param      string	$attributeName	The name of the attribute to add
	* @param      string	$atributeValue	The value of the attribute
	*/
	function setAttribute( $attributeName, $attributeValue )
	{
		if ( !isset( $this->attributeDefinition[$attributeName] ) )
		{
			patErrorManager::raiseNotice(
				PATFORMS_NOTICE_ATTRIBUTE_NOT_SUPPORTED,
				"The attribute '".$attributeName."' is not supported by the form, skipped it. [".get_class( $this )."]"
			);
			return true;
		}

		$this->attributes[$attributeName]	=	$attributeValue;

		return true;
	}

   /**
	* adds several attributes at once to the form's attribute collection.
	* Any existing attributes will be overwritten.
	*
	* @access     public
	* @param      array	$attributes	The attributes to add
	* @see        setAttribute()
	*/
	function setAttributes( $attributes )
	{
		if ( !is_array( $attributes ) )
		{
			return patErrorManager::raiseError(
				PATFORMS_NOTICE_ARRAY_EXPECTED,
				"setAttributes: array expected"
			);
		}

		foreach ( $attributes as $attributeName => $attributeValue )
		{
			$this->setAttribute( $attributeName, $attributeValue );
		}

		return true;
	}

   /**
	* retrieves the value of a form attribute.
	*
	* @access     public
	* @param      string	$attribute	The name of the attribute to retrieve
	* @return     mixed	$attributeValue	The value of the attribute, or false if it does not exist in the attributes collection.
	* @see        setAttribute()
	*/
	function getAttribute( $attribute )
	{
		if ( !isset( $this->attributes[$attribute] ) )
		{
			return false;
		}

		return $this->attributes[$attribute];
	}

   /**
	* retrieves all attributes of the form, or only the specified attributes.
	*
	* @access     public
	* @param      array	$attributes	Optional: The names of the attributes to retrieve. Only the attributes that exist will be returned.
	* @return     array	$result		The attributes
	* @see        getAttribute()
	*/
	function getAttributes( $attributes = array() )
	{
		if ( empty( $attributes ) )
		{
			return $this->attributes;
		}

		$result	=	array();
		foreach ( $attributes as $attribute )
		{
			if ( $attributeValue = $this->getAttribute( $attribute ) )
			{
				$result[$attribute]	=	$attributeValue;
			}
		}

		return $result;
	}

   /**
	* Loads the default attribute values into the attributes collection. Done directly
	* on startup (in the consructor).
	*
	* The action defaults to the path of the current script, with session
	* ID appended automatically, if SID has been defined.
	*
	* @access     public
	* @return     bool	$success	Always returns true.
	* @see        $attributeDefaults
	*/
	function loadAttributeDefaults()
	{
		foreach ( $this->attributeDefinition as $attributeName => $attributeDef )
		{
			if ( isset( $attributeDef['default'] ) )
			{
				$this->attributes[$attributeName]	=	$attributeDef['default'];
			}

			if ( $attributeName == 'action' )
			{
				$this->attributes[$attributeName]	=	$_SERVER['PHP_SELF'];
				/**
				 * session has been started, append session ID
				 */
				if ( defined( 'SID' ) )
					$this->attributes[$attributeName] .= '?' . SID;
			}
		}

		return true;
	}

   /**
	* retrieves the form's current submitted state.
	*
	* If autoValidate is used, it will check for the submitVar and
	* set the submitted flag accordingly
	*
	* @access     public
	* @return     bool	$state	True if it has been submitted, false otherwise.
	* @see        setSubmitted(), setAutoValidate()
	* @see        submitted
	*/
	function isSubmitted()
	{
		if ( $this->submitted === true )
		{
			return true;
		}

		if ( !isset( $this->submitVar ) )
		{
			return	false;
		}

		if ( !$this->autoValidate )
		{
			return	false;
		}

		if ( isset( $_GET[$this->submitVar] ) || isset( $_POST[$this->submitVar] ) )
		{
			$this->setSubmitted( true );
		}

		return $this->submitted;
	}

   /**
	* Creates a new patForms_Creator object
	*
	* @static
	* @access     public
	* @return     object	$creator	The creator object, or a patError object on failure
	*/
	function createCreator( $type )
	{
		return patForms::_createModule( 'Creator', $type );
	}

   /**
	* get the element name of the form
	*
	* @access     public
	* @return     string	name of the form
	*/
	function getElementName()
	{
		return $this->elementName;
	}

   /**
	* get next error offset
	*
	* @access     public
	* @return     integer
	*/
	function getErrorOffset( $requiredCodes = 100 )
	{
		$offset					=	$this->nextErrorOffset;
		$this->nextErrorOffset	=	$this->nextErrorOffset + $requiredCodes;
		return	 $offset;
	}

   /**
	* add error codes and messages for validator method
	*
	* @access     public
	* @param      array	defintions
	* @param      integer	offset for the error codes
	*/
	function addValidatorErrorCodes( $defs, $offset = 1000 )
	{
		foreach ( $defs as $lang => $codes )
		{
			if ( !isset( $this->validatorErrorCodes[$lang] ) )
			{
				$this->validatorErrorCodes[$lang]	=	array();
			}

			foreach ( $codes as $code => $message )
			{
				$this->validatorErrorCodes[$lang][($offset+$code)]	=	$message;
			}
		}
	}

   /**
	* add a validation error to the whole form
	*
	* This can be achieved by adding a validation rule to the form.
	*
	* @access     public
	* @param      integer	$code
	* @param      array	$vars	fill named placeholder with values
	* @return     boolean $result	true on success
	* @see        addRule()
	*/
	function addValidationError( $code, $vars = array() )
	{
		$error		=	false;
		$lang		=	$this->locale;
		$element	=	$this->getElementName();

		// find error message for selected language
		while ( true )
		{
			// error message matches language code
			if ( isset( $this->validatorErrorCodes[$lang][$code] ) )
			{
				$error	=	array( "element" => $element, "code" => $code, "message" => $this->validatorErrorCodes[$lang][$code] );
				break;
			}
			// no message found and no fallback-langauage available
			else if ( $lang == "C" )
			{
				break;
			}

			$lang_old	=	$lang;

			// look for other languages
			if ( strlen( $lang ) > 5 )
			{
				list( $lang, $trash	) =	explode( ".", $lang );
			}
			else if ( strlen( $lang ) > 2 )
			{
				list( $lang, $trash	) =	explode( "_", $lang );
			}
			else
			{
				$lang	=	"C";
			}

			// inform developer about missing language
			patErrorManager::raiseNotice(
				PATFORMS_NOTICE_VALIDATOR_ERROR_LOCALE_UNDEFINED,
				"Required Validation Error-Code for language: $lang_old not available. Now trying language: $lang",
				"Add language definition in used element or choose other language"
			);

		}

		// get default Error!
		if ( !$error )
		{
	 		patErrorManager::raiseWarning(
				PATFORMS_WARNING_VALIDATOR_ERROR_UNDEFINED,
				"No Error Message for this validation Error was defined",
				"Review the error-definition for validation-errors in your element '$element'."
			);
			$error	=	array( "element" => $element, "code" => 0, "message" => "Unknown validation Error" );
		}

		// insert values to placeholders
		if ( !empty( $vars ) )
		{
			foreach ( $vars as $key => $value )
			{
				$error["message"]	=	str_replace( "[". strtoupper( $key ) ."]", $value, $error["message"] );
			}
		}

		array_push( $this->validationErrors, $error );
		$this->valid	=	false;
		return  true;
	}

   /**
	* retreives a new element id, used to give each added element a unique id for this
	* form (id can be overwritten by setting the id attribute specifically).
	*
	* @access     private
	* @return     int	$elementId	The new element id.
	*/
	function getElementId()
	{
		$this->elementCounter++;
		return 'pfo'.$this->elementCounter;
	}

   /**
	* attach an observer
	*
	* @access     public
	* @param      object	patForms_Observer
	* @see        createObserver()
	* @uses       patForms_Element::createObserver()
	*/
	function attachObserver( &$observer, $where = PATFORMS_OBSERVER_ATTACH_TO_ELEMENTS )
	{
		/**
		 * attach the observer to all elements
		 */
		if ( ( $where & PATFORMS_OBSERVER_ATTACH_TO_ELEMENTS ) == PATFORMS_OBSERVER_ATTACH_TO_ELEMENTS )
		{
			$cnt	=	count( $this->elements );
			for ( $i = 0; $i < $cnt; ++$i )
			{
				$this->elements[$i]->attachObserver( $observer );
			}
		}

		/**
		 * attach the observer to the form
		 */
		if ( ( $where & PATFORMS_OBSERVER_ATTACH_TO_FORM ) == PATFORMS_OBSERVER_ATTACH_TO_FORM )
		{
			$this->observers[] = &$observer;
		}
		return true;
	}

   /**
 	* Retrieve the content for the start of the form, including any
	* additional content, e.g. global scripts if the scripts option
	* is enabled.
	*
	* @access     public
	* @return     string	$formStart	The form start content
	* @todo       use format to build a dynamic method
	*/
	function serializeStart()
	{
		$methodName	=	"serializeStart".ucfirst( $this->getFormat() ).ucfirst( $this->getMode() );

		if ( !method_exists( $this, $methodName ) )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_METHOD_FOR_MODE_NOT_AVAILABLE,
				"Method for patForms mode '".$this->getMode()."' (".$methodName.") is not available."
			);
		}

		return	$this->$methodName();
	}

   /**
	* Serializes the form's start element for html format, in default mode.
	*
	* @access     private
	* @return     mixed	$formStart	The serialized start content, or a patError object.
	*/
	function serializeStartHtmlDefault()
	{
		$attributes	= $this->getAttributesFor( $this->format );
		if ( patErrorManager::isError( $attributes ) )
		{
			return $attributes;
		}

		$content	=	patForms_Element::createTag( 'form', 'opening', $attributes );

		if ( $this->optionEnabled( 'scripts' ) )
		{
			$content	.=	$this->getScripts();
		}

		return $content;
	}

   /**
	* Serializes the form's start element for html format, in readonly mode.
	*
	* @access     private
	* @return     mixed	$formStart	The serialized start content, or a patError object.
	*/
	function serializeStartHtmlReadonly()
	{
		$attributes	= $this->getAttributesFor( $this->format );
		if ( patErrorManager::isError( $attributes ) )
		{
			return $attributes;
		}

		return patForms_Element::createTag( 'form', 'opening', $attributes );
	}

   /**
 	* Retrieve the content for the end of the form.
	*
	* @access     public
	* @return     string	$formEnd	The form end content
	*/
	function serializeEnd()
	{
		$methodName	=	"serializeEnd".ucfirst( $this->getFormat() ).ucfirst( $this->getMode() );

		if ( !method_exists( $this, $methodName ) )
		{
			return patErrorManager::raiseError(
				PATFORMS_ERROR_METHOD_FOR_MODE_NOT_AVAILABLE,
				"Method for patForms mode '".$this->getMode()."' (".$methodName.") is not available."
			);
		}

		return	$this->$methodName();
	}

   /**
	* Retrieves the content for the end of the form for html format,
	* in default mode.
	*
	* @access     private
	* @return     string	$formEnd	The form end content
	*/
	function serializeEndHtmlDefault()
	{
		return	patForms_Element::createTag( 'form', 'closing' );
	}

   /**
	* Retrieves the content for the end of the form for html format,
	* in readonly mode.
	*
	* @access     private
	* @return     string	$formEnd	The form end content
	*/
	function serializeEndHtmlReadonly()
	{
		return	$this->serializeEndHtmlDefault();
	}

   /**
	* validates the current attribute collection according to the attributes definition
	* and the given output format, and returns the list of valid attributes.
	*
	* @access     private
	* @param      string	$format		The output format to retrieve the attributes for.
	* @return     mixed	$attributes	The list of attributes, or false if failed.
	*/
	function getAttributesFor( $format )
	{
		$attributes	=	array();

		foreach ( $this->attributeDefinition as $attributeName => $attributeDef )
		{
			if ( !isset( $this->attributes[$attributeName] ) )
			{
				if ( $attributeDef["required"] )
				{
					return patErrorManager::raiseError(
						PATFORMS_ERROR_ATTRIBUTE_REQUIRED,
						'patForms needs the attribute "'.$attributeName.'" to be set.',
						'See the patForms attribute definition of patForms for a complete attribute reference.'
					);
				}

				continue;
			}

			$attributeValue	=	$this->attributes[$attributeName];

			if ( !in_array( $format, $attributeDef["outputFormats"] ) )
			{
				continue;
			}

			if ( isset( $attributeDef["format"] ) )
			{
				if ( !$this->_checkAttributeFormat( $attributeValue, $attributeDef["format"] ) )
				{
					return patErrorManager::raiseError(
						PATFORMS_ERROR_CAN_NOT_VERIFY_FORMAT,
						"Format '".$attributeDef["format"]."' could not be verified for patForms attribute '".$attributeName."' => '".$attributeValue."'"
					);
				}
			}

			$attributes[$attributeName]	=	$attributeValue;
		}

		return $attributes;
	}

   /**
	* checks the format of an attribute value according to the given format.
	*
	* @access     private
	* @param      mixed	$attributeValue	The attribute value to check
	* @param      string	$format			The format to check the attribute value against
	* @return     bool	$result			True if format check succeeded, false otherwise.
	* @see        createAttributes()
	* @todo       Implement this method sometime
	*/
	function _checkAttributeFormat( $attributeValue, $format )
	{
		return true;
	}

   /**
	* Enables a patForms option.
	*
	* See the {@link $options} property for an exhaustive list of available options.
	*
	* @access     public
	* @param      string	$option		The option to enable
	* @param      array	$params		Optional parameters for the option
	* @return     mixed	$result		True on success, patError object otherwise.
	* @see        disableOption()
	* @see        optionEnabled()
	* @see        $options
	*/
	function enableOption( $option, $params = array() )
	{
		if ( !in_array( $option, array_keys( $this->options ) ) )
		{
			return patErrorManager::raiseNotice(
				PATFORMS_NOTICE_INVALID_OPTION,
				'The option "'.$option.'" is not a valid patForms option.'
			);
		}

		$this->options[$option]['enabled']	=	true;
		$this->options[$option]['params']	=	$params;

		// now update all available elements too
		$cnt = count( $this->elements );
		for ( $i=0; $i < $cnt; $i++ )
		{
			$this->elements[$i]->enableOption( $option, $params );
		}

		return true;
	}

   /**
	* Disables a patForms option
	*
	* See the {@link $options} property for an exhaustive list of available options.
	*
	* @access     public
	* @param      string	$option	The option to disable
	* @return     mixed	$result	True on success, patError object otherwise.
	* @see        enableOption()
	* @see        optionEnabled()
	* @see        $options
	*/
	function disableOption( $option )
	{
		if ( !in_array( $option, array_keys( $this->options ) ) )
		{
			return patErrorManager::raiseNotice(
				PATFORMS_NOTICE_INVALID_OPTION,
				'The option "'.$option.'" is not a valid patForms option.'
			);
		}

		$this->options[$option]['enabled']	=	false;

		// now update all available elements too
		$cnt = count( $this->elements );
		for ( $i=0; $i < $cnt; $i++ )
		{
			$this->elements[$i]->disableOption( $option );
		}

		return true;
	}

   /**
	* Checks whether the given option is enabled.
	*
	* @access     public
	* @param      string	$option		The option to check
	* @return     bool	$enabled	True if enabled, false otherwise.
	* @see        enableOption()
	* @see        disableOption()
	* @see        $options
	*/
	function optionEnabled( $option )
	{
		if ( !isset( $this->options[$option] ) )
			return false;

		return $this->options[$option]['enabled'];
	}

   /**
	* Set the form to auto validate
	*
	* If you use this method, patForms will check the _GET and _POST variables
	* for the variable you specified. If it is set, patForms assumes, that
	* the form has been submitted.
	*
	* When creating a start tag for the form, the value will be inserted automatically.
	*
	* @access     public
	* @param      string	$submitVar
	*/
	function setAutoValidate( $submitVar )
	{
		$this->autoValidate	=	true;
		$this->submitVar	=	$submitVar;
	}

   /**
	* register a new event
	*
	* After registering an event, you may register one or more
	* event handlers for this event an then trigger the event.
	*
	* This lets you extend the functionality of patForms.
	*
	* @access     public
	* @param      string	event name
	* @return     boolean	true, if event could be registered
	* @see        registerEventHandler()
	* @see        triggerEvent()
	*/
	function registerEvent( $name )
	{
		$event	=	'on' . $name;
		if ( in_array( $event, $this->_validEvents ) )
		{
			return patErrorManager::raiseNotice(
												PATFORMS_NOTICE_EVENT_ALREADY_REGISTERED,
												'Event "'.$event.'" already has been registered or is built-in event'
												);
		}
		array_push( $this->_validEvents, $event );
		return true;
	}

   /**
	* Register an event handler
	*
	* An event handler can be any valid PHP callback. You may pass
	* one of the following values:
	* - string functionname to call a globally declared function
	* - array( string classname, string methodname) to call a static method
	* - array( object obj, string methodname) to call a method of an object
	*
	* When the handler is called, two parameters will be passed:
	* - object form  : a patForms object
	* - string event : the name of the event has should be handled.
	*
	* An event handler should always return true. If false is returned,
	* the event will be cancelled.
	*
	* Currently handlers for the following events can be registered:
	* - onSubmit
	* - onSuccess
	* - onError
	*
	* @access     public
	* @param      string	event name
	* @param      mixed	event handler
	* @return     boolean	true, if the handler could be registered
	* @see        triggerEvent()
	* @see        $_validEvents
	*/
	function registerEventHandler( $event, $handler )
	{
		if ( !in_array( $event, $this->_validEvents ) )
		{
			return patErrorManager::raiseError(
												PATFORMS_ERROR_UNKNOWN_EVENT,
												'Cannot register event handler for unknown event "' . $event .'".'
												);
		}

		if ( !is_callable( $handler ) )
		{
			return patErrorManager::raiseError(
												PATFORMS_ERROR_INVALID_HANDLER,
												'Event handler is not callable.'
												);
		}

		if ( !isset( $this->_eventHandler[$event] ) )
		{
			$this->_eventHandler[$event]	=	array();
		}

		$this->_eventHandler[$event][]	=	&$handler;
		return true;
	}

   /**
	* set event handler object.
	*
	* An event handler object is used to handle all
	* registered events. The object has to provide methods
	* for all events it should handle, the names of the methods
	* have to be the same as the names of the events.
	*
	* @access     public
	* @param      object	event handler object
	* @param      array	method names, used to change the names of the methods
	* @return     boolean
	*/
	function registerEventHandlerObject( &$obj, $methods = array() )
	{
		if ( empty( $methods ) )
		{
			foreach ( $this->_validEvents as $event )
			{
				if ( !method_exists( $obj, $event ) )
					continue;

				$methods[$event]	=	$event;
			}
		}

		foreach ( $methods as $event => $method )
		{
			if ( !isset( $this->_eventHandler[$event] ) )
			{
				$this->_eventHandler[$event]	=	array();
			}

			$this->_eventHandler[$event][]	=	array( &$obj, $method );
		}

		return	true;
	}

   /**
	* Trigger an event
	*
	* In most cases there's no need to call this event
	* from outside the class. The method is declared public
	* to allow you to trigger custom events.
	*
	* @access     public
	* @param      string	Event name. The event name must not contain 'on', as this will be
	*					prefixed automatically.
	*/
	function triggerEvent( $event )
	{
		$handlerName	=	'on' . $event;

		if ( !isset( $this->_eventHandler[$handlerName] ) || empty( $this->_eventHandler[$handlerName] ) )
		{
			return true;
		}

		$cnt	=	count( $this->_eventHandler[$handlerName] );
		for ( $i = 0; $i < $cnt; $i++ )
		{
			$result	=	call_user_func( $this->_eventHandler[$handlerName][$i], $this, $event );
			if ( $result == false )
			{
				return $result;
			}
		}
		return true;
	}

   /**
	* Serializes the entire form to XML, all elements included
	*
	* @access     public
	* @param      string	$namespace	Optional namespace to use for the tags
	* @return     string	$xml		The XML representation of the form
	* @see        patForms_Element::toXML()
	* @todo       needs patForms_Element, maybe switch to PEAR::XML_Util
	*/
	function toXML( $namespace = null )
	{
		$tagName = 'Form';

		// prepend Namespace
		if ( $namespace != null )
		{
			$tagName	=	$namespace.':'.$tagName;
		}

		// get all attributes
		$attributes	=	$this->getAttributes();

		// create valid XML attributes
		foreach ( $attributes as $key => $value )
		{
			$attributes[$key]	=	strtr( $value, $this->xmlEntities );
		}

		$elements = '';
		for ( $i = 0; $i < $this->elementCounter; $i++ )
		{
			$elements .= $this->elements[$i]->toXML( $namespace );
		}

		return	patForms_Element::createTag( $tagName, "full", $attributes, $elements );
	}

   /**
	* Set a static property.
	*
	* Static properties are stored in an array in a global variable,
	* until PHP5 is ready to use.
	*
	* @static
	* @param      string	property name
	* @param      mixed	property value
	* @see        getStaticProperty()
	*/
	function setStaticProperty( $property, &$value )
	{
		$GLOBALS["_patForms"][$property]	=	&$value;
	}

   /**
	* Get a static property.
	*
	* Static properties are stored in an array in a global variable,
	* until PHP5 is ready to use.
	*
	* @static
	* @param      string	property name
	* @return     mixed	property value
	* @see        setStaticProperty()
	*/
	function &getStaticProperty( $property )
	{
		if ( isset( $GLOBALS["_patForms"][$property] ) )
		{
			return	$GLOBALS["_patForms"][$property];
		}
		return	patErrorManager::raiseWarning(
			PATFORMS_ERROR_NO_STATIC_PROPERTY,
			'Static property "'.$property.'" could not be retreived, it does not exist.'
		);
	}

   /**
	* Retrieves the form's name
	*
	* If no name is set, it will use 'patForms' as name.
	*
	* @access     public
	* @return     string	$name	The name of the form.
	*/
	function getName()
	{
		if ( isset( $this->attributes['name'] ) )
			return $this->attributes['name'];
		return 'patForms';
	}

   /**
	* get the javascript for the form
	*
	* This is still in alpha state. It will later
	* allow client side validation if the element
	* provides this feature.
	*
	* @access     public
	* @return     string	javascript needed by the form
	* @todo       make this dependent on the format
	* @todo       add changeable linebreaks
	*/
	function getScripts()
	{
		foreach ($this->elements as $element) {
			$element->registerJavascripts($this);
		}

		$globalJavascript = implode ("", $this->javascripts['global']);
		$instances = implode ("", $this->javascripts['instance']);

		$script	= '<script type="text/javascript" language="Javascript1.3">' . "\n"
				. $globalJavascript . "\n\n" . $instances . "\n"
				. '</script>';

		return $script;

		/*
		$globalJavascript	=	'';
		$instances			=	'';

		$displayedTypes		=	array();

		$cnt = count( $this->elements );
		for ( $i = 0; $i < $cnt; ++$i )
		{
			$instances	.=	$this->elements[$i]->getInstanceJavascript();

			$type	=	$this->elements[$i]->getElementName();
			if ( in_array( $type, $displayedTypes ) )
				continue;

			array_push( $displayedTypes, $type );
			$globalJavascript	.=	$this->elements[$i]->getGlobalJavascript();
		}

		$cnt = count( $this->_rules );
		for ( $i = 0; $i < $cnt; ++$i )
		{
			$instances	.=	$this->_rules[$i]['rule']->getInstanceJavascript();


			$type	=	$this->_rules[$i]['rule']->getRuleName();
			if ( in_array( $type, $displayedTypes ) )
				continue;

			array_push( $displayedTypes, $type );

			$globalJavascript	.=	$this->_rules[$i]['rule']->getGlobalJavascript();
		}

		$script	=	'<script type="text/javascript" language="Javascript1.3">' . "\n"
				.	$globalJavascript . "\n\n" . $instances . "\n"
				.	'</script>';

		return $script;
		*/
	}

	private $javascripts = array(
		'global' => array(),
		'instance' => array()
	);

	function registerGlobalJavascript($type, $script) {

		$this->javascripts['global'][$type] = $script;
	}

	function registerInstanceJavascript($script) {

		$this->javascripts['instance'][] = $script;
	}

   /**
	* anounce a change in the element to all observers
	*
	* @access     private
	* @param      string		property that changed
	* @param      mixed		new value of the property
	*/
	function _announce( $property, $value )
	{
		$cnt = count( $this->observers );
		for ( $i = 0; $i < $cnt; $i++ )
		{
			$this->observers[$i]->notify( $this, $property, $value );
		}
		return true;
	}
}
