<?php
/**
 * patForms_Creator_DB examples
 *
 * patForms_Creator is a subpackage of patForms that provides
 * several formbuilder classes that create a form from
 * a datasource.
 *
 * WARNING:
 * The Creator subpackage is still in devel state!
 *
 * @access     public
 * @package    patForms
 * @subpackage Examples
 * @author     Stephan Schmidt <schst@php-tools.net
 * @author     Sebastian Mordziol <argh@php-tools.net>
 * @license    LGPL, see license.txt for details
 * @link       http://www.php-tools.net
 */

	/**
	 * Main examples prepend file, needed *only* for the examples framework!
	 */
	//include_once 'patExampleGen/prepend.php';
	//$exampleGen->displayHead( 'Example' );

	include('include/common.php');

	// EXAMPLE START ------------------------------------------------------

	/**
	 * main patForms class
	 */
	require_once ('patForms.php');

	/**
	 * patErrorManager class
	 */
	require_once ('patErrorManager.php');


	// create the creator :)
	$creator = &patForms::createCreator( 'Propel' );

	// create the form object from the given propel Object class instance

	 require_once('model/general/UserProfile.php');
	 $userProfile = UserProfilePeer::retrieveByPK(1);
	 $form =& $creator->create( $userProfile );

	//$wikipage = WikipagePeer::retrieveByPK('wiki');
	//$form =& $creator->create($wikipage);

	// create the needed renderer
	$renderer       =&      patForms::createRenderer( "Array" );

	// set the renderer
	$form->setRenderer( $renderer );

	// use auto-validation
	$form->setAutoValidate( 'save' );

	// serialize the elements
	$elements = $form->renderForm();


	// ERROR DISPLAY ------------------------------------------------------
	if ( $form->isSubmitted() )
	{
			displayErrors( $form ); // see patExampleGen/customFunctions.php
	}

	// DISPLAY FORM ------------------------------------------------------
	displayForm( $form, $elements ); // see patExampleGen/customFunctions.php

	 /**
	* Takes a patForms object, asks it if there are any validation
	* errors and displays them if need be.
	*
	* NOTE: this is just a helper method for our examples collection,
	* so that you may concentrate on the relevant parts of the examples.
	* It does in no way represent the way it should be done :)
	*
	* @access     public
	* @param      object  &$form  The patForms object to use
	*/
	function displayErrors( &$form )
	{
			// get the errors from the form object - if there are none,
			// this returns false so it is easy to check if there are any.
			$errors = $form->getValidationErrors();

			// if there are any errors, display them.
			if ( $errors )
			{
					echo '<div class="piErrors">';
					echo '  <div class="piErrorsTitle">Validation failed</div>';
					echo '  <div class="piErrorsContent">';

					// the errors collection is an associative array with the
					// field names as keys, so we go through that.
					foreach ( $errors as $elementName => $elementErrors )
					{
							$element =& $form->getElementByName( $elementName );

							// each element can have more than one error - this
							// is rare, but can happen so this is an indexed array
							// with one error in each row.
							foreach ( $elementErrors as $row => $error )
							{
									echo '          <div class="piError">';
									echo '                  <b>'.$element->getAttribute( 'label' ).':</b> '.$error['message'].'('.$error['element'].' element error #'.$error['code'].')<br/>';
									echo '          </div>';
							}
					}

					echo '  </div>';
					echo '</div>';
			}
			// no errors, tell the world everything is fine
			else
			{

					echo '<div class="piHint">Validation successful.</div>';
			}
	}


	/**
	* Displays a standard form from the examples collection when the
	* form is rendered via the array renderer. Does not work for any
	* other examples.
	*
	* NOTE: this is just a helper method for our examples collection,
	* so that you may concentrate on the relevant parts of the examples.
	* It does in no way represent the way it should be done :)
	*
	* @access     public
	* @param      object  &$form          The current form object
	* @param      array   $elements       The rendered elements from the
	* @return
	* @see
	*/
	function displayForm( &$form, $elements )
	{
		// output the opening form tag
		echo $form->serializeStart();

		echo "<table>\n";
		foreach ( $elements as $element ) {
		}
		echo "</table>\n";

		// display all elements
		foreach ( $elements as $element )
		{
			if (!isset($element['description'])) {
				// would choke a warning on hidden fields
				// strange enough, we've no $element['type'] for hidden inputs
				echo $element['element'] . "\n";
				continue;
			}

			echo '<div style="margin-bottom:8px;">';
			echo $element['label']."<br>";
			echo "<div>".$element["element"]."</div>";
			echo "<i>".$element["description"]."</i><br>";

			echo '</div>';

			//} else {
				//echo "<tr><td>".$element['description']."</td><td>".$element['element']."</td></tr>\n";
			//}
		}

		// submit button, closing form tag
		echo '<input type="submit" name="save" value="Save form"/><br><br>';
		echo $form->serializeEnd();


		// form submitted? display all form values
		if ( $form->isSubmitted() ) {
				$els =& $form->getElements();
				$cnt = count( $els );

				echo '<div class="piValues">';
				echo '  <div class="piValuesTitle">Submitted form values</div>';
				echo '  <div class="piValuesContent">';
				echo '          <table cellpadding="2" cellspacing="0" border="0">';

				for ( $i = 0; $i < $cnt; $i++ ) {
						echo '<tr>';
						echo '  <td>'.$els[$i]->getAttribute('label').'</td><td>&nbsp;:&nbsp;</td><td>'.$els[$i]->getValue().'</td>';
						echo '</tr>';
				}

				echo '          </table>';
				echo '  </div>';
				echo '</div>';
		}
	}
