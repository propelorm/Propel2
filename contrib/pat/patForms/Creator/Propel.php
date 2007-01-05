<?php
/**
 * patForms Creator Propel
 *
 * @package    patForms
 * @subpackage Creator
 */


/**
 * Error: could not connect to the database
 */
 define( 'PATFORMS_CREATOR_PROPEL_ERROR_NO_CONNECTION', 'patForms:Creator:Propel:01' );

/**
 * patForms Creator DB
 *
 * @access     protected
 * @package    patForms
 * @subpackage Creator
 * @author     Bert Van den Brande <cyruzb@gmail.com>
 * @license    LGPL, see license.txt for details
 * @link       http://www.php-tools.net
 */
class patForms_Creator_Propel extends patForms_Creator
{
	private static $creoleTypeMapping = array(
		CreoleTypes::BOOLEAN    	=>'Radio',		// BOOLEAN 			= 1;
		CreoleTypes::BIGINT     	=>'String',		// BIGINT 			= 2;
		CreoleTypes::SMALLINT   	=>'String',		// SMALLINT 		= 3;
		CreoleTypes::TINYINT    	=>'String',		// TINYINT 			= 4;
		CreoleTypes::INTEGER    	=>'String',		// INTEGER 			= 5;
		CreoleTypes::CHAR       	=>'String',		// CHAR 			= 6;
		CreoleTypes::VARCHAR    	=>'String',		// VARCHAR 			= 7;
		CreoleTypes::FLOAT      	=>'String',		// FLOAT 			= 8;
		CreoleTypes::DOUBLE     	=>'String',		// DOUBLE 			= 9;
		CreoleTypes::DATE       	=>'Date',		// DATE 			= 10;
		CreoleTypes::TIME       	=>'String',		// TIME 			= 11;
		CreoleTypes::TIMESTAMP  	=>'Date',		// TIMESTAMP 		= 12;
		CreoleTypes::VARBINARY  	=>'String',		// VARBINARY 		= 13;
		CreoleTypes::NUMERIC    	=>'String',		// NUMERIC 			= 14;
		CreoleTypes::BLOB       	=>'String',		// BLOB 			= 15;
		CreoleTypes::CLOB       	=>'String',		// CLOB 			= 16;
		CreoleTypes::TEXT       	=>'Text',		// TEXT 			= 17;
		CreoleTypes::LONGVARCHAR	=>'Text',		// LONGVARCHAR 		= 17;
		CreoleTypes::DECIMAL    	=>'String',		// DECIMAL 			= 18;
		CreoleTypes::REAL       	=>'String',		// REAL 			= 19;
		CreoleTypes::BINARY     	=>'String',		// BINARY 			= 20;
		CreoleTypes::LONGVARBINARY	=>'String',		// LONGVARBINARY 	= 21;
		CreoleTypes::YEAR       	=>'String',		// YEAR 			= 22;
		CreoleTypes::ARR   	    	=>'String',
		CreoleTypes::OTHER      	=>'String'
	);

   /**
	* Create a form from a propel instance
	*
	* @access     public
	* @param      mixed	$object		An instance of a Propel object
	* @param      array	$options	Any options the creator may need
	* @return     object 	$form		The patForms object, or a patError object on failure.
	*/
	function &create( $object, $options = array() )
	{
		// Propel stuff
		$propel_peer = $object->getPeer();
		$propel_mapBuilder = $propel_peer->getMapBuilder(); // Not sure if we're gonna need this one
		$propel_tablename = constant(get_class($propel_peer) . '::TABLE_NAME');
		$propel_tableMap = $propel_mapBuilder->getDatabaseMap()->getTable($propel_tablename);

		// The form
		$form =& patForms::createForm( null, array( 'name' => 'patForms_Creator_Form' ) );

		$propel_cols = $propel_tableMap->getColumns();
		foreach ($propel_cols as $propel_colname => $propel_col) {

			// phpName can be altered by editing the schema.xml,
			// thus I think, we should lowercase()/ucfirst() this
			$propel_colname = strtolower($propel_colname);
			$el_displayname = ucFirst($propel_colname);
			// this could be omitted of course, but I think, it's a
			// convenient way to get more safe request properties
			$el_name = $propel_tablename . '[' . $propel_colname . ']';

			$el_attr = array(
				'edit'  => 'yes',
				'title' => $el_displayname,
				'label' => $el_displayname,
				'name'  => $el_name,
				'description' => $el_displayname
			);

	  //// Obsolete ?
			// Parse column info to element type info
			//$type_info = $this->parseTypeInfoFromColumn($propel_col);
			// Merge extra element attributes
			//$el_attr = array_merge( $el_attr, $type_info['attributes'] );

			// Is the element required ? Can we retrieve this info from the Column object ?
			$el_attr['required'] = 'yes';
			// Value: for now we use default to set the value. Is there a better (more correct) way to do this ?
			$el_attr['default'] = $object->{'get'.$propel_col->getPhpName()}();

			if ($propel_col->isPrimaryKey()) {
				$el_type = 'hidden';
			} else {
				$el_type = self::$creoleTypeMapping[$propel_col->getCreoleType()];
			}

			$el = &$form->createElement($el_name, $el_type, null);
			// patForms will choke when we try to set attributes
			// that don't match the element type. So we'll ask.
			foreach ($el_attr as $name => $value) {
				if ($el->hasAttribute($name)) {
					$el->setAttribute($name, $value);
				}
			}
			$form->addElement($el);
		}

		return  $form;
	}

  // Seems this function will become obsolete if we use the static $creoleTypeMapping
	function parseTypeInfoFromColumn ( $column ) {

		return array(
			  'type'       => 'String',
			  'attributes' => array()
		);
	}
}
