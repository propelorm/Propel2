<?php
/*
 * $Id$
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

require_once 'Structures/DataGrid.php';

define('STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS', 2);
define('STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS', 3);
define('STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_VISIBLE', 4);
define('STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_HIDDEN', 5);

/**
 *
 * NOTE: Structures_DataGrid_Propel extends Structures_DataGrid, so all Datagrid functionality is available.
 *
 * // Propel and Propel project classes must be in the include_path
 *
 * // Propel Class name : User
 * $dg =& new Structures_DataGrid_Propel('User');
 * // DO NOT OVERLOOK THE & IN =& ABOVE!!
 * 			if you do, you'll spend a few hours figuring out why the $dg->render() method is spitting 
 *			out a blank table when everything seems just right ... like I just did.
 * // SERIOUSLY, INSTANTIATE THIS SUCKER BY REFERENCE :: you've been warned.
 *
 * // limit to 10 rows
 * $c = new Criteria();
 * $c->setLimit(10);
 * $dg->setCriteria($c);
 *
 * // choose what columns must be displayed
 * $dg->setColumnMode(STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS);
 * $dg->showColumn('FirstName');				// column header will be 'FirstName' when set like this
 * $dg->showColumn('LastName', 'Last Name'); 	// now column header will be 'Last Name' : Nice and friednly
 * $dg->showColumn('FullName', 'Full Name'); 	// will call the Propel Objects ->getFullName() method to fill this in
 *												// since you may want to print a value that is not just a column in
 *												// the table your Propel object represnets
 *
 * // for convenience, the showColumn method can also be used to preset params for the 
 * // Structures_DataGrid_Column that will be built for the outputted GridColumn when the build()
 * // method is finally called
 * $dg->showColumn('Email', 'Email', null, null, null, 'printGridEmailLink($emailIndex=Email )');
 * 
 * // so, when the Email column is being printed for the row, it will use the printGridEmailLink function
 * // to output the value for the column and make it a mailto link like the function below would:
 * // refer to: http://pear.php.net/manual/en/package.structures.structures-datagrid.formatter.php for mor info on that
 * function printGridEmailLink($params) {
 * 		extract($params);
 * 		return "<a href=\"mailto:{$record[$emailIndex]}\">{$record[$emailIndex]}</a>";
 * }
 *
 * // generate the datagrid
 * $dg->build();
 *
 * // Display the datagrid
 * $dg->render();
 *
 * NOTE:	The functionality of this version differs from that of the PHP5 one
 *		 	We are using the phpName of the variable to show/hide/etc instead of the in the build() methods and
 *			the showColumn( ... ) methods, so instead of doing this: 
 *
 *			$dg->showColumn('FIRST_NAME') 	to pull the FIRST_NAME column from the 
 *											creole result set in the build() method, we do
 *			$dg->showColumn('FirstName') 	to pull the value returned from the $propelObj->getFirstName() method
 *
 * @author 		Marc <therebel@free.fr> : PHP5 version
 * @author 		Steve Lianoglou <steve@arachnedesign.net> : PHP4 Version
 * @version 	$Revision$
 * @copyright 	Copyright (c) 2005: LGPL - See LICENCE
 * @package 	propel.contrib-php4
 */

class Structures_DataGrid_Propel extends Structures_DataGrid {

	/**
	 * Contains column visibility information.
	 * @var array
	 * @access private
	 */
	/* private */
	var $columnVisibility = array();
	
	
	/**
	 * Store the column properties to use in the Structures_DataGrid_Column constructor
	 * for each column that we will print
	 *
	 * each propel column name will have an array of values
	 * $columnProperties[COLUMN_NAME]
	 *		['label']
	 *		['orderBy']
	 * 		['attribs'] => array()
	 *		['autoFillValue']
	 *		['formatter']
	 * @var array
	 * @access private
	 */	
	var $columnProperties = array();
	
	
	/**
	 * The Column visibility mode.
	 * Possible values:
	 *
	 * STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS
	 * STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS
	 *
	 * @var integer
	 * @access private
	 */
	/* private */
	var $columnMode;

	/**
	 * String containing the peerName.
	 * @var string
	 * @access private
	 */
	/* private */
	var $peerName;

	/**
	 * String containing the className of the propel Object.
	 * @var string
	 * @access private
	 */
	/* private */
	var $className;

	/**
	 * Criteria of the Select query.
	 * @var criteria
	 * @access private
	 */
	/* private */
	var $criteria;

	/**
	 * List of primary keys
	 * @var array
	 * @access public
	 */
	/* public */
	var $primaryKeys;

	/**
	 * The Constructor
	 *
	 * Classname is specific to Structures_Datagrid_Propel
	 *
	 * The other parameters are needed to construct the parent Structures_DataGrid Class.
	 *
	 * @param string className
	 * @param string limit
	 * @param string render
	 */
	function Structures_DataGrid_Propel($className = null, $limit = null, $render = DATAGRID_RENDER_TABLE) {
		if ( !class_exists($className) ) {
			include_once $className.'.php';
			include_once $className.'Peer'.'.php';
		}

		$this->setClassName($className);
		$this->setPeerName($className.'Peer'); // Is this always true ?
		parent::Structures_DataGrid($limit,null,$render);

		// set the default column policy
		$this->setColumnMode(STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS);
		$this->criteria = new Criteria();
	} // constructor

	/**
	 * Set the criteria for select query
	 *
	 * @param Criteria c
	 */
	/*public*/
	function setCriteria(/* Criteria */ &$c) {
		$this->criteria =& $c;
	} // function setCriteria

	/**
	 * Set the class
	 *
	 * @param 	string className
	 * @return 	void
	 */
	/* public */
	function setClassName($className) {
		$this->className = $className;
	} // function setClassName

	/**
	 * Get the class name
	 *
	 * @return 	string	className
	 */
	/* public */ 
	function getClassName() {
		return $this->className;
	} // function getClassName

	
	/**
	 * Set the peerName
	 * 
	 * @param 	string 	peerName
	 * @return 	void
	 */
	/* private */
	function setPeerName($peerName) {
		$this->peerName = $peerName;
	} // function setPeerName

	/**
	 * Get the peer name
	 *
	 * @return string peerName
	 */
	/* public */
	function getPeerName() {
		return $this->peerName;
	} // function getPeerName

	/**
	 * Get the visibility of a column
	 *
	 * @param 	string 	column name to check for
	 * @return 	boolean	true if column is set to hidden
	 */
	/* public */
	function isColumnHidden($column) {
		if( $this->checkColumn($column, STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_HIDDEN) && 
			($this->columnMode == STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS) ) {
				return true;
		}

		if( !$this->checkColumn($column, STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_VISIBLE) && 
			($this->columnMode == STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS) ) {
				return true;
		}

		return false;
	} // function isColumnHidden
	
	/**
	 * Check the state of a column
	 *
	 * @return boolean true if column is set to state
	 */
	/* private */
	function checkColumn($column, $state) {
		if(isset($this->columnVisibility[$column])) {
			return ($this->columnVisibility[$column] == $state);
		} else {
			return false;
		}
	} // function checkColumn

	/**
	 * Sets the default visibility mode
	 *
	 * This must be either:
	 * STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS or
	 * STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS
	 *
	 * @param	string	$column column name
	 * @return 	void
	 */
	/* public */
	function setColumnMode($mode) {
		if($mode != STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS && $mode != STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS) {
			// throw new PropelException('STRUCTURES_DATAGRID_PROPEL::setColumnMode(): invalid mode passed.');
			return new PropelException('STRUCTURES_DATAGRID_PROPEL::setColumnMode(): invalid mode passed.');
           }
           
		$this->columnMode = $mode;
	} // function setColumnMode

	/**
	 * Tell Structures_Datagrid_Propel it should hide this column
	 * <code>$column</code> is now passed as the phpName of the column, not the Peer name
	 *
	 * @param 	string	$column phpName of the column
	 * @return 	void
	 */
	/* public */
	function hideColumn($column) {
		$this->columnVisibility[$column] = STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_HIDDEN;
	} // function hideColumn


	/**
	 * Tell Structures_Datagrid_Propel it should show this column,
	 * optionaly can set all the other values that will be generated during the 
	 * new Structures_DataGrid_Column all for this column during the build() process, which looks like:
	 *		 void Structures_DataGrid_Column (
	 *   	 		string $columnName, 
	 *   	 		string $fieldName 
	 *   	 		[, string $orderBy = null 
	 *   	 		[, array $attribs = array() 
	 *   	 		[, string $autoFillValue = null 
	 *   	 		[, string $formatter = null]]]])
	 *
	 * <code>$column</code> is now passed as the phpName of the column, not the Peer name
	 *
	 * @param	string 	$column column name
	 * @param 	string 	$columnName optional label to use for this column that we are showing
	 * @param 	string 	the column to order this sucker by
	 * @param	array 	attributes to tack on to this column header
	 * @param 	string 	the value to fill in if the value in the row's column is null/empty
	 * @param	string	the callback function to use to write the row's element in this column
	 * @param 	array 	optional array to set the attribs for the DataGrid_Column for this column
	 * @return 	void
	 */
	/* public */ 
	function showColumn($column, $columnName = null, $orderBy = null, $attribs = null, $autoFillValue = null,$formatter = null) {
		$this->columnVisibility[$column] = STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_VISIBLE;
		
		// set default columName, and orderBy, and makde $fieldName = $column for now
		$columnName = ( is_null($columnName) ) ? $column : $columnName;
		$orderBy = ( is_null($orderBy) ) ? $column : $orderBy;
		$this->setColumnPropertyValues($column, $columnName, $column, $orderBy, $attribs, $autoFillValue, $formatter);
	} // function showColumn
	
	
	/**
	 * Quickly set the the column attributes for the DataGrid_Column that will be generated during
	 * the build() method
	 * 		Note that if the value of any of these vars is null, it won't reset the property in 
	 * 		$this->columnProperties[$column] to null, just in case it was set to something else
	 *		(somewhere else) 
	 *
 	 * @param	string 	$column phpName of the column
	 * @param 	string 	$columnName optional label to use for this column that we are showing
	 * @param 	string 	the column to order this sucker by
	 * @param	array 	attributes to tack on to this column header
	 * @param 	string 	the value to fill in if the value in the row's column is null/empty
	 * @param	string	the callback function to use to write the row's element in this column
	 * @param 	array 	optional array to set the attribs for the DataGrid_Column for this column
	 * @return 	void
	 */
	function setColumnPropertyValues($column, $columnName = null, $fieldName, $orderBy = null, $attribs = null, $autoFillValue = null, $formatter = null) {
		if ( !is_null($columnName) ) 	{ $this->columnProperties[$column]['columnName']	= $columnName; }
		if ( !is_null($fieldName) ) 	{ $this->columnProperties[$column]['fieldName']		= $fieldName; }
		if ( !is_null($orderBy) ) 		{ $this->columnProperties[$column]['orderBy'] 		= $orderBy; }
		if ( !is_null($attribs) ) 		{ $this->columnProperties[$column]['attribs'] 		= $attribs; }
		if ( !is_null($autoFillValue) ) { $this->columnProperties[$column]['autoFillValue'] = $autoFillValue; }
		if ( !is_null($formatter) ) 	{ $this->columnProperties[$column]['formatter'] 	= $formatter; }
	}
	
	
	/**
	 * Set a particular property for the Structures_DataGrid_Column object that will be created
	 * during the build() method for the <code>$column</code> entry
	 * 
	 * @param string 	phpName of column to set property for
	 * @param string	name of parameter to set, should only be element of:
	 *					{columnName | fieldName | orderBy | attribs | autoFillValue | formatter } 
	 *					(maybe we should check that, but leave that for later, a different propert than
	 *					 can't really hurt)
	 * @param mixed		value to set the property to
	 * @return void
	 */
	function setColumnProperty($column, $property, $value = null) {
		$this->columnProperties[$column][$property] = value;
	}
	
	
	/**
	 * Returns the specified property to retrieve for a particular column
	 *
	 * @param string	phpName of column in the datagrid
	 * @param string	name of property to fetch from said column
	 * @return mixed	whatever the property is set to, or if that particulary property
	 *						hasn't been set, will return null
	 */
	function getColumnProperty($column, $property) {
		$value = ( isset($this->columnProperties[$column][$property]) ) ? $this->columnProperties[$column][$property] : null;
		
		switch ( $property ) {
			case 'attribs':
			/* Note:	this method *WILL ALWAYS* return at least an 'class' attribute
			 *			so the header and table cells can be selected/formated with css
			 *			like: 
			 *				th.COLUMN_NAME-cell { .. put header formatting here .. }
			 *				td.COLUMN_NAME-cell { .. put normal td formatting for column here .. }
			 *
			 *			If there are no attribs, it will just make an array w/ the class attrib
			 *			If there is an attrib array and no 'class' attribute, then it will add a default
			 *				one of the form <code>COLUMN_NAME-cell</code>
			 *			If there is an attrib array, with the 'class' attrib, it will leave it untouched
			 */
				$value = ( is_array($value) ) ? $value : array();
				if ( !isset($value['class']) ) {
					$value['class'] = $column . '-cell';
				}
				break;
			
			default:
				break;
		}
		
		return $value;
	} // function getColumnProperty

		
	/* this version of build actually instantiates the Propel Objects w/ the peer's doSelect method */
	function build() {
		$objs =& call_user_func(array($this->getPeerName(), 'doSelect'), $this->criteria);
		$this->primaryKeys = array();
		$instanceVars = array();
		
		// ------------------- getting instance vars
		$map =& call_user_func(array($this->getPeerName(), 'getTableMap'));
		foreach ($map->columns as $key => $col) {
			$instanceVars[] = $col->phpName;
			if ( $col->isPrimaryKey() ) {
				$this->primaryKeys[$col->phpName] = $col->phpName;
			} // $col->isPrimaryKey()
		} // for-each
		// ------------------------------------------
		
		$limit = count($objs);
		
		$dataset = array();
		$columns = array();

		for ( $n = 0; $n < $limit; $n++ ) { 
			$obj =& $objs[$n];
			$row = array();
			
			foreach( $instanceVars as $key => $varName ) {
				// save the row value
				$row[$varName] = $obj->{'get'.$varName}();
				
				// save the list of propel header column name
				$columns[$varName] = $varName;
			} // foreach instanceVars as 
			
			// add any other visible columns (set w/ the showColumn() method)
			// and are not instanceVars of the Propel object, but rather an additional
			// method that is defined in your subclass of your TableBase.php classes
			foreach( $this->columnVisibility as $column => $visibility ) {
				if ( !($this->isColumnHidden($column)) && !(isset($row[$column])) ) {
					$row[$column] = $obj->{'get'.$column}();
				} // !(this->isColumnHidden($column)) && !(isset($row[$column]))
			} // foreach this->columnVisibility as column
			
			$dataset[] = $row;
		} // for n < $limit

		// Explicitly set the data source to an Array type because if the resultset was empty
		// the bind method (for some reason) tries to bind this w/ a DB_DataObject driver
		$this->bind($dataset, null, 'Array'); 

		if ($this->columnMode == STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS) {
			foreach($columns as $tmp_id => $column) {
				if (!$this->isColumnHidden($column)) {
					$this->addColumn(
						new Structures_DataGrid_Column(
								$this->getColumnProperty($column, 'columnName'),
								$this->getColumnProperty($column, 'fieldName'),
								$this->getColumnProperty($column, 'orderBy'),
								$this->getColumnProperty($column, 'attribs'),
								$this->getColumnProperty($column, 'autoFillValue'),
								$this->getColumnProperty($column, 'formatter') 
							));
				}
			}
		} else {
			foreach($this->columnVisibility as $column => $visibility) {

				if (!$this->isColumnHidden($column)) {
					$this->addColumn(
						new Structures_DataGrid_Column(
								$this->getColumnProperty($column, 'columnName'),
								$this->getColumnProperty($column, 'fieldName'),
								$this->getColumnProperty($column, 'orderBy'),
								$this->getColumnProperty($column, 'attribs'),
								$this->getColumnProperty($column, 'autoFillValue'),
								$this->getColumnProperty($column, 'formatter') 
							));
				}
			}
		}
	}
} // class Structures_DataGrid_Propel

?>
