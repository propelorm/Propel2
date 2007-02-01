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

require_once 'HTML/QuickForm.php';

define('HTML_QUICKFORM_PROPEL_NO_COLUMNS', 2);
define('HTML_QUICKFORM_PROPEL_ALL_COLUMNS', 3);
define('HTML_QUICKFORM_PROPEL_COLUMN_MADE_VISIBLE', 4);
define('HTML_QUICKFORM_PROPEL_COLUMN_MADE_HIDDEN', 5);

/* On large foreign table resultsets propel choked */
# ini_set('memory_limit', '50M');

/**
 *
 *  NOTE: HTML_QuickForm_Propel extends HTML_QuickForm, so all QuickForm functionality is available.
 *
 *  A fictive example:
 *
 *  $className = 'book';
 *  $id = '7'; // existing item
 *  $id = null; // when no id is passed, it's assumed we are creating a new item
 *
 *  $quickForm = new HTML_QuickForm_Propel($className, $id); // optionally pass it to the constructor
 *  $quickForm->setAction('/Bookstore/Form');
 *  $quickForm->addElement('header', '', 'HTML_QuickForm_Propel');
 *  $quickForm->setId($id);
 *  $quickForm->setClassName($className);
 *  $quickForm->setTarget('_self');
 *  $quickForm->setMethod('post');
 *
 *  // use this to override the default behaviour for
 *  // foreign table select boxes, UserPeer::UNAME will be shown in the select list.
 *  // It defaults to the first string column in the foreign table.
 *
 *  $quickForm->joinColumn(bookPeer::PUBLISHER_ID,UserPeer::UNAME);
 *
 *  // default to all columns shown
 *  $quickForm->setColumnMode(HTML_QUICKFORM_PROPEL_ALL_COLUMNS);
 *  $quickForm->hideColumn('PASS');
 *
 *  // or default to no columns shown
 *  $quickForm->setColumnMode(HTML_QUICKFORM_PROPEL_NO_COLUMNS);
 *  $quickForm->showColumn('NAME'); // column name without table prefix.
 *  $quickForm->showColumn('UNAME');
 *  $quickForm->showColumn('USER_INFO');
 *  $quickForm->showColumn('EMAIL');
 *  $quickForm->showColumn('FEMAIL');
 *  $quickForm->showColumn('URL');
 *  $quickForm->showColumn('PASS');
 *
 *  // generate the form
 *  $quickForm->build();
 *
 *  // after the form is build, it's possible to modify the generated elements
 *  $quickForm->getElement('NAME')->setSize(10); // manually tune this element
 *
 *  if ($quickForm->validate()) {
 *          $quickForm->freeze();
 *
 *          // save the object we have editted
 *          $quickForm->save();
 *  } else {
 *          $quickForm->toHtml(); // or any other QuickForm render option
 *  }
 *
 * TODO: map all creoleTypes to usefull formelements
 *
 * @author     Rob Halff <info@rhalff.com>
 *   some improvements by Zoltan Nagy (sunshine@freemail.hu)
 * @version    $Rev$
 * @copyright  Copyright (c) 2005 Rob Halff: LGPL - See LICENCE
 * @package    propel.contrib
 */

class HTML_QuickForm_Propel extends HTML_QuickForm {

		/**
		 * ID of the Propel Object.
		 * @var        integer
		 * @access     private
		 */
		private $id;

		/**
		 * Contains column visibility information.
		 * @var        array
		 * @access     private
		 */
		private $columnVisibility = array();

		/**
		 * Contains titles of columns.
		 * @var        array
		 * @access     private
		 */
		private $columnTitle = array();

		/**
		 * The Column visibility mode either.
		 * Possible values:
		 *
		 * HTML_QUICKFORM_PROPEL_ALL_COLUMNS
		 * HTML_QUICKFORM_PROPEL_NO_COLUMNS
		 *
		 * @var        integer
		 * @access     private
		 */
		private $columnMode;

		/**
		 * String containing the peerName.
		 * @var        string
		 * @access     private
		 */
		private $peerName;

		/**
		 * String containing the className of the propel Object.
		 * @var        string
		 * @access     private
		 */
		private $className;

		/**
		 * The Column objects.
		 *
		 * @var        array
		 * @access     private
		 */
		private $cols;

		/**
		 * The Object being operated on.
		 * @var        object
		 * @access     private
		 */
		private $obj;

		/**
		 * Seperator value.
		 *
		 * In case the option list will be build by multiple values
		 * This is the value these fields will be seperated with
		 *
		 * @var        string
		 * @access     private
		 */
		private $seperator = ' ';

		/**
		 *
		 * Not used yet.
		 *
		 * @var        array
		 * @access     private
		 */
		private $joinMap = array();

		/**
		 * The default QuickForm rule type to use.
		 * Either server or client
		 *
		 * @var        string
		 * @access     private
		 */
		private $defaultRuleType = 'server';


		/**
		 * This is used in the QuickForm DateElement
		 * @var        string
		 * @access     private
		 */
		private $lang = 'en';

		/**
		 * Rulemapping should cover all available propel rules
		 *
		 * @var        array
		 * @access     private
		 */
		private $ruleMapping =  array(
						'mask'=>'regex',
						'maxLength'=>'maxlength',
						'minLength'=>'minlength',
						'maxValue'=>'maxvalue',
						'minValue'=>'minvalue',
						'required'=>'required',
						'validValues'=>'validvalues',
						'unique'=>'unique'
						);

		/**
		 *
		 * CreoleType to QuickForm element mapping
		 *
		 * @var        array
		 * @access     private
		 */
		private $typeMapping = array(
						CreoleTypes::BOOLEAN    =>'radio',
						CreoleTypes::BIGINT     =>'text',
						CreoleTypes::SMALLINT   =>'text',
						CreoleTypes::TINYINT    =>'text',
						CreoleTypes::INTEGER    =>'text',
						CreoleTypes::NUMERIC    =>'text',
						CreoleTypes::DECIMAL    =>'text',
						CreoleTypes::REAL       =>'text',
						CreoleTypes::FLOAT      =>'text',
						CreoleTypes::DOUBLE     =>'text',
						CreoleTypes::CHAR       =>'text',
						CreoleTypes::VARCHAR    =>'text',
						CreoleTypes::LONGVARCHAR=>'textarea',
						CreoleTypes::TEXT       =>'textarea',
						CreoleTypes::TIME       =>'text',
						CreoleTypes::TIMESTAMP  =>'date',
						CreoleTypes::DATE       =>'date',
						CreoleTypes::YEAR       =>'text',
						CreoleTypes::VARBINARY  =>'text',
						CreoleTypes::BLOB       =>'text',
						CreoleTypes::CLOB       =>'text',
						CreoleTypes::BINARY     =>'text',
						CreoleTypes::LONGVARBINARY=>'text',
						CreoleTypes::ARR        =>'text',
						CreoleTypes::OTHER      =>'text'
								);

		/**
		 *
		 * The Constructor
		 *
		 * Classname and id are specific to HTML_QuickForm_Propel
		 *
		 * The other parameters are needed to construct the parent QuickForm Class.
		 *
		 * @param      string className
		 * @param      string id
		 * @param      string formName
		 * @param      string method
		 * @param      string action
		 * @param      string target
		 * @param      array attributes
		 * @param      boolean trackSubmit
		 *
		 */
		public function __construct($className = null, $id = null, $formName='HTML_QuickForm_Propel', $method='post', $action='', $target='_self', $attributes=null, $trackSubmit = false)
		{
				$this->setClassName($className);
				$this->setPeerName($className.'Peer'); // Is this always true ?
				$this->setId($id);
				parent::HTML_QuickForm($formName, $method, $action, $target, $attributes, $trackSubmit);

				// set the default column policy
				$this->setColumnMode(HTML_QUICKFORM_PROPEL_ALL_COLUMNS);
		}

		/**
		 *
		 * NOT IMPLEMENTED
		 *
		 * Allows for creating complex forms.
		 * Note that limit 1 will always be added to the criteria.
		 * Because we can only edit one record/row at a time.
		 *
		 * However we will be able to join tables in complex ways.
		 *
		 * @param      Criteria
		 *
		 */
		public function setCriteria(Criteria $c)
		{
				$c->setLimit(1);
				$this->criteria = $c;
		}

		/**
		 *
		 * Set the action of this form
		 *
		 * @param      string action
		 *
		 */
		public function setAction($action)
		{
				$attributes = array('action'=>$action);
				$this->updateAttributes($attributes);
		}

		/**
		 *
		 * Set method of this form, e.g. post or get
		 *
		 * @param      string method
		 *
		 */
		public function setMethod($method)
		{
				$attributes = array('method'=>$method);
				$this->updateAttributes($attributes);
		}

		/**
		 *
		 * Set the target of this form
		 *
		 * @param      string target
		 *
		 */
		public function setTarget($target)
		{
				$attributes = array('target'=>$target);
				$this->updateAttributes($attributes);
		}

		/**
		 *
		 * Set the id of the object we need to get
		 *
		 * @param      string id
		 *
		 */
		public function setId($id)
		{
				$this->id = $id;
		}

		/**
		 *
		 * Set the class
		 *
		 * @param      string className
		 *
		 */
		public function setClassName($className)
		{
				$this->className = $className;
		}

		/**
		 *
		 * Get the class name
		 *
		 * @return     string className
		 *
		 */
		public function getClassName()
		{
				return $this->className;
		}

		private function setPeerName($peerName)
		{
				$this->peerName = $peerName;
		}

		/**
		 *
		 * Get the peer name
		 *
		 * @return     string peerName
		 *
		 */
		public function getPeerName()
		{
				return $this->peerName;
		}

		/**
		 * Build the form
		 *
		 * @return     void
		 *
		 */
		public function build()
		{

				if (!class_exists($this->peerName)) {
						// some autoloading, dunno if it should belong here.
						// or in the users autoload function
						//$dbMap = call_user_func(array($this->peerName, 'getMapBuilder'));
						// TODO: implement this.
				}

				if (empty($this->id)) {
						// create empty instance of this class
						$this->obj = new $this->className();
				} else {
						// find the object.
						if (!$this->obj = call_user_func(array($this->peerName, 'retrieveByPK'), $this->id)) {
								// for help me god..  what to do ?
								throw new PropelException("HTML_QuickForm_Propel::build(): $this->peerName::retrieveByPK($this->id) failed.");
						}

				}

				// note: getTableMap is protected by default.
				// so do this handstand to get the tableMap
				$mapBuilder = call_user_func(array($this->peerName, 'getMapBuilder'));

				// Note: $dbMap is re-used below to determine foreign table
				$dbMap = $mapBuilder->getDatabaseMap();

				// Get the column names for this table.
				$this->cols = $dbMap->getTable(constant("$this->peerName::TABLE_NAME"))->getColumns();

				// Do it the HTML_QuickForm way and use defaultValues
				// instead of setValue() on every element.
				// we have to first loop through the columns
				foreach ($this->cols as $colName=>$col) {


						$elementType = $this->typeMapping[$col->getCreoleType()];

						if ($elementType == 'date') {
								// null returns timestamp
								$value = $this->obj->{'get'.$col->getPhpName()}(null);
						} else {

								$value  = $this->obj->{'get'.$col->getPhpName()}();

						}

						$defaultValues[$colName] = $value;
				}

				$this->setDefaults($defaultValues);

				foreach ($this->cols as $colName=>$col) {

						if ($this->isColumnHidden($colName)) {
								continue;
						}

						if ($col->isPrimaryKey()) {
								// create a hidden field with primary key
								if (!$this->checkColumn($colName, HTML_QUICKFORM_PROPEL_COLUMN_MADE_VISIBLE)) {
								        $this->addElement('hidden', $col->getColumnName(), $col->getColumnName());
								        continue;
								}
						}

						// element's title
						$colTitle = $this->getColumnTitle($colName);

						if ($col->isForeignKey()) {

								// TODO: check if user created an optional method for populating the select form
								// this populating of the select box is just some way to show joined items in a form.
								// it could also be checkboxes, radio buttons or whatever kind of widget.

								$relatedTable = $dbMap->getTable($col->getRelatedTableName());
								$relatedCols = $relatedTable->getColumns();
								$relatedPeer = $relatedTable->getPhpName().'Peer';

								if (is_callable($relatedPeer, 'doSelect')) {


								        // TODO: allow set this criteria.
								        $c = new Criteria;

								        $relatedList = call_user_func(array($relatedPeer,'doSelect'), $c);

								        $colConstant        = constant($this->peerName.'::'.$colName);
								        //$relatedColConstant = constant($relatedPeer.'::'.$relatedCol->getColumnName());
								        $relatedGetter      = 'getPrimaryKey';


								        // TODO: this needs to be array based, to support multiple foreign columns
								        if (isset($this->joinMap[$colConstant])) {
								                // translate to getter
								                $relatedColConstant = $this->joinMap[$colConstant];
								                if (!$relatedTable->containsColumn($relatedColConstant)) {
								                        // throw exception, there is no such column
								                        throw new PropelException('HTML_QuickForm_Propel::build(): there is no column named '.$relatedTable->normalizeColName($relatedConstant).'in '.$relatedTable->getTableName().' while trying to build the select list');
								                }
								                $nameColumn = $relatedTable->getColumn($relatedColConstant);
								                $relatedGetter = 'get'.$nameColumn->getPhpName();
								        } else {
								                //auto detection
								                // determine the first string column
								                foreach ($relatedCols as $relatedCol) {
								                        if ($relatedCol->getType() == 'string') {
								                                $relatedGetter = 'get'.$relatedCol->getPhpName();
								                                break;
								                        }
								                }
								        }


								        $selectList = array();

								        // TODO: not hardcoded here.
								        $selectList[null] = _('Please selection an option');

								        foreach ($relatedList as $relObj) {
								                $key = $relObj->getPrimaryKey();

								                if (false OR $yesWannaUseEntireObjectsToUseItInTemplate) {
								                        // TODO: IMPLEMENT THIS.
								                        $selectList[$key] = $relObj;
								                } elseif (false OR $forceSomeKindOfColumnToBeDisplayed) {
								                        // TODO: IMPLEMENT THIS.
								                } else {
								                        $selectList[$key] = $relObj->{$relatedGetter}();
								                }
								        }

								        if (count($selectList) > 1) { // value of 1 depends on select message being set.
								                $select =& $this->addElement('select', $colName, $colTitle, $selectList);
								        } else {
								                // what to do if no records exists in the foreign table ?
								                $this->addElement('static', $colName, $colTitle, _('No Records'));
								        }

								}

								// do some recursion ?

						} else {

								//TODO: the mapping is not so generic anymore (to many exceptions)
								$elementType = $this->typeMapping[$col->getCreoleType()];

								if ($col->getCreoleType() == CreoleTypes::BOOLEAN) {
								        // TODO: describe how to override these options.
					$radio = array();
								        $radio[] = HTML_QuickForm::createElement('radio', null, null, 'Yes', true);
								        $radio[] = HTML_QuickForm::createElement('radio', null, null, 'No', false);
								        $el = $this->addGroup($radio,  $colName, $colName);


								} else {

								        $el = $this->addElement(
								                        $elementType,
								                        $colName,
								                        $colTitle);

								        if ($elementType == 'text') {
								                $el->setMaxLength($col->getSize());
								        }

								        if ($col->getCreoleType() == CreoleTypes::TIMESTAMP) {

								                /* Option Info:
								                   var $_options = array(
								                   'language'         => 'en',
								                   'format'           => 'dMY',
								                   'minYear'          => 2001,
								                   'maxYear'          => 2010,
								                   'addEmptyOption'   => false,
								                   'emptyOptionValue' => '',
								                   'emptyOptionText'  => '&nbsp;',
								                   'optionIncrement'  => array('i' => 1, 's' => 1)
								                   );
								                 */

								                // hmm, don't like it but there seems to be no public method
								                // to set an option afterwards
								                $el->_options['language'] = $this->lang;
								                // TODO: is the format always the same in propel ?
								                $el->_options['format']   = 'Y-F-d H:i:s';

								        }

								}
								// add an html id to the element
								$this->addElementId($el, $colName);

								//$el->setValue($value);

								// required rule for NOT NULL columns
								if ($col->isNotNull()) {
								    // TODO: What error message should we use?
								    $this->addRule($colName,
								        $this->getColumnTitle($colName) . ' is required',
								        'required');
								}

								if ($col->hasValidators()) {

								        foreach ($col->getValidators() as $validatorMap) {

								                $this->addRule($colName,
								                                $validatorMap->getMessage(),
								                                $this->ruleMapping[$validatorMap->getName()],
								                                $validatorMap->getValue(),
								                                $this->defaultRuleType
								                              );

								        }
								}

						}
				}

				// should HTML_QuickForm_Propel add this ?
				$this->addElement('submit', 'submit', 'Submit');

				// do this for the developer, can't think of any case where this is unwanted.
				$this->applyFilter('__ALL__', 'trim');

		}

		/**
		 *
		 * Use it to change the locale used for the Date element
		 *
		 * @param      string locale
		 * @return     void
		 *
		 */
		public function setLang($lang)
		{
				$this->lang = $lang;
		}

		/**
		 *
		 * Save the form.
		 *
		 * @return     void
		 *
		 */
		public function save()
		{
				$this->copyToObj();
				$this->obj->save();
		}

		/**
		 *
		 * Copy form values to Obj.
		 *
		 * @return     void
		 *
		 */
		public function copyToObj()
		{
				// TODO: check what process does, if we leave out anything important.

				if (!isset($this->cols)) {
						// throw some error, form cannot be saved before it is build.
						throw new PropelException('HTML_QuickForm_Propel::save(): form cannot be saved before it is build.');
				}

				foreach ($this->cols as $colName=>$col) {

						// Has the form got this element?
						if ($this->isColumnHidden($colName))
						{
							continue;
						}

						$value = $this->getElementValue($colName);
						if ($value instanceof HTML_QuickForm_Error)
						{
							// TODO: What should we do if an error has occured?
							continue;
						}
						$elementType = $this->typeMapping[$col->getCreoleType()];

						// quickform doesn't seem to give back a timestamp, so calculate the date manually.
						if ($elementType == 'date') {

								$date = array(
								                'D' => null,
								                'l' => null,
								                'd' => null,
								                'M' => null,
								                'm' => null,
								                'F' => null,
								                'Y' => null,
								                'y' => null,
								                'h' => null,
								                'H' => null,
								                'i' => null,
								                's' => null,
								                'a' => null,
								                'A' => null
								             );

								foreach ($value as $key=>$val)  {
								        $date[$key] = $val[0];

								}

								$value = mktime($date['h'], $date['m'], $date['s'], $date['M'], $date['d'], $date['Y']);
						}

						$this->obj->{'set'.$col->getPhpName()}($value);
				}
		}

		/**
		 *
		 * Get the object we are operating on.
		 *
		 * @return     object a propel object
		 *
		 */
		public function getObj()
		{
				return $this->obj;
		}

		/**
		 * What to do if a delete button is added
		 * and the user clicks on it, after the object has been delete in save()
		 */
		public function onDelete()
		{


		}

		public function createDeleteButton()
		{
				return $this->addElement('submit', 'delete', 'Delete');
		}

		public function isColumnHidden($column)
		{
				if ($this->checkColumn($column, HTML_QUICKFORM_PROPEL_COLUMN_MADE_HIDDEN) && $this->columnMode == HTML_QUICKFORM_PROPEL_ALL_COLUMNS) {
						return true;
				}

				if (!$this->checkColumn($column, HTML_QUICKFORM_PROPEL_COLUMN_MADE_VISIBLE) && $this->columnMode == HTML_QUICKFORM_PROPEL_NO_COLUMNS) {
						return true;
				}

				return false;
		}

		private function checkColumn($column, $state)
		{
				if (isset($this->columnVisibility[$column])) {
						return ($this->columnVisibility[$column] == $state);
				} else {
						return false;
				}
		}

		/**
		 *
		 * Sets the default visibility mode
		 *
		 * This must be either:
		 * HTML_QUICKFORM_PROPEL_NO_COLUMNS or
		 * HTML_QUICKFORM_PROPEL_ALL_COLUMNS
		 *
		 * @param      string $column column name
		 * @return     void
		 *
		 */
		public function setColumnMode($mode)
		{

				if ($mode != HTML_QUICKFORM_PROPEL_NO_COLUMNS && $mode != HTML_QUICKFORM_PROPEL_ALL_COLUMNS) {

						throw new PropelException('HTML_QuickForm_Propel::setColumnMode(): invalid mode passed.');

				}

				$this->columnMode = $mode;
		}

		/**
		 *
		 * Tell HTML_QuickForm_Propel it should hide this column
		 * It is now passed like ID instead of somePeer::ID
		 * The latter is better, but the array_keys of the columns are in ID format and not somePeer::ID
		 *
		 * @param      string $column column name
		 * @return     void
		 *
		 */

		public function hideColumn($column)
		{
				$this->columnVisibility[$column] = HTML_QUICKFORM_PROPEL_COLUMN_MADE_HIDDEN;
		}

		/**
		 *
		 * Tell HTML_QuickForm_Propel it should show this column
		 *
		 * It is now passed like ID instead of somePeer::ID
		 * The latter is better, but the array_keys of the columns are in ID format and not somePeer::ID
		 *
		 * @param      string $column column name
		 * @param      string $title Title for the column, not required
		 * @return     void
		 */
		public function showColumn($column, $title = NULL)
		{
				$this->columnVisibility[$column] = HTML_QUICKFORM_PROPEL_COLUMN_MADE_VISIBLE;
				if ($title !== NULL)
				{
					$this->setColumnTitle($column, $title);
				}
		}

		/**
		 *
		 * assign a title to the column
		 *
		 * @param      string $column
		 * @param      string $title
		 * @return     void
		 */
		public function setColumnTitle($column, $title)
		{
			$this->columnTitles[$column] = $title;
		}

		/**
		 *
		 * returns column's title
		 *
		 * @param      string $column
		 * @return     void
		 */
		public function getColumnTitle($column)
		{
			// TODO: check if $column exists
			return (array_key_exists($column, $this->columnTitles))
				? $this->columnTitles[$column]
				: $column;
		}

		/**
		 *
		 * Try to automatically join all relatedTables.
		 * NOT IMPLEMENTED
		 *
		 * @param      boolean $bool
		 * @return     void
		 */
		public function autoJoin($bool)
		{
				$this->autoJoin = $bool;
		}

		/**
		 * Override this if you don't like the (strtolower) default
		 *
		 * @param      HTML_QuickForm_Element $el
		 * @param      string $colName
		 * @return     void
		 */
		protected function addElementId($el, $colName)
		{
				$el->updateAttributes(array('id'=>strtolower($colName)));
		}

		/**
		 *
		 * Set the default rule typef
		 * @param      string $type
		 * @return     void
		 *
		 */
		public function setDefaultRuleType($type)
		{
				$this->defaultRuleType = $type;
		}

		/**
		 *
		 * UNFINISHED
		 *
		 * Probably it would be nice to be able to add this to the schema xml
		 *
		 * TODO: further implement multiple columns for the select list
		 *
		 * @var        colName constant
		 * @var        foreignColName mixed (constant/array of columnName constants)
		 * @var        $seperator string Only used if foreignColName is an array
		 */

		public function joinColumn($colName, $foreignColName, $seperator = null)
		{
				if (isset($seperator)) {
						$this->seperator = $seperator;
				}
				$this->joinMap[$colName] = $foreignColName;
		}

}
