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
 * A fictive example:
 * // Propel and Propel project classes must be in the include_path
 *
 * // Propel Class name : Report
 * $dg =& new Structures_DataGrid_Propel('Report');
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
 * $dg->showColumn('ACTIVE');
 * $dg->showColumn('TITLE', 'Report Title'); // this prints the column header as 'Report Title', not as 'TITLE'
 * $dg->showColumn('ID');
 *
 * // generate the datagrid
 * $dg->build();
 *
 * // add two columns to edit the row and checkbox for further operations
 * $dg->addColumn(new Structures_DataGrid_Column('', null, null, array('width' => '4%'), null, 'printEditLink()'));
 * $dg->addColumn(new Structures_DataGrid_Column('', null, null, array('width' => '1%'), null, 'printCheckbox()'));
 *
 * // Display the datagrid
 * $dg->render();
 *
 * @author Marc <therebel@free.fr> 
 * @author Steve Lianoglou <steve@arachnedesign.net> : port to php4
 * @version $Rev$
 * @copyright Copyright (c) 2005: LGPL - See LICENCE
 * @package propel.contrib
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
	 * Overrides the default COLUMN_HEADING to a user friendly label
	 * @var array
	 * @access private
	 * @see Structures_DataGrid_Propel::showColumn
	 */
	var $columnLabels = array();

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
	 * @param string className
	 * @return void
	 */
	/* public */
	function setClassName($className) {
		$this->className = $className;
	} // function setClassName

	/**
	 * Get the class name
	 *
	 * @return string className
	 */
	/* public */ 
	function getClassName() {
		return $this->className;
	} // function getClassName

	
	/**
	 * Set the peerName
	 * 
	 * @param string peerName
	 * @return void
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
	 * @param string column name to check for
	 * @return boolean true if column is set to hidden
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
	 * @param string $column column name
	 * @return void
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
	 * It is now passed like ID instead of somePeer::ID
	 * The latter is better, but the array_keys of the columns are
	 * in ID format and not somePeer::ID
	 *
	 * @param string $column column name
	 * @return void
	 */
	/* public */
	function hideColumn($column) {
		$this->columnVisibility[$column] = STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_HIDDEN;
	} // function hideColumn

	/**
	 * Tell Structures_Datagrid_Propel it should show this column,
	 * optionaly sets the label name for this column to a 'friendly' label.
	 *
	 * It is now passed like ID instead of somePeer::ID
	 * The latter is better, but the array_keys of the columns are in ID format and not somePeer::ID
	 *
	 * @param string $column column name
	 * @param string $label optional label to use for this column that we are showing
	 * @return void
	 */
	/* public */ 
	function showColumn($column, $label = '') {
		$this->columnVisibility[$column] = STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_VISIBLE;
		$this->setColumnLabel($column, $label);
	} // function showColumn


	/**
	 * Sets the column name to $label to render a table with 'friendly' column names
	 *
	 * @param string $labelName set this to a friendly column title, for instance:
	 *				 if $column is 'FIRST_NAME', set $labelName to 'First Name'
	 *				 so the column heading will be 'First Name' as opposed to 'FIRST_NAME'
	 * @return void
	 */
	function setColumnLabel($column, $label = '') {
		$this->columnLabels[$column] = ( $label == '' ) ? $column : $label;
	} // function setColumnLabel
	
	/**
	 * Retrieve the friendly colum name for $column
	 * if there is none, just return $column
	 * 
	 * @param string the column to get the friendly name for
	 * @return string the name to use for this column
	 */
	function getColumnLabel($column) {
		return ( isset($this->columnLabels[$column]) ) ? $this->columnLabels[$column] : $column;
	} // function getColumnLabel
	
	/**
	 * Build the datagrid
	 *
	 * @return void
	 */
	/* public */
	function build() {
		$mapBuilder = call_user_func(array($this->getPeerName(), 'getMapBuilder'));
		$dbMap = $mapBuilder->getDatabaseMap();
		
		// -----
		// $cols = $dbMap->getTable(constant($this->getPeerName()."::TABLE_NAME"))->getColumns(); --> PHP5 version
		$evalString = '$tableName = ' . $this->getPeerName() . '::TABLE_NAME();';
		eval($evalString);
		$table =& $dbMap->getTable($tableName);
		$cols =& $table->getColumns();
		// ---- php5 one-liner turns to a php4/4-liner :-)
		
		$rs =& call_user_func(array( $this->getPeerName(), 'doSelectRS'), $this->criteria);

		$dataset = array();
		$columns = array();
		$this->primaryKeys = array();
		$class = $this->getClassName();

		while ( $rs->next() ) { // use Creole ResultSet methods to iterate over resultset
			$obj = new $class();
			$obj->hydrate($rs);

			$row = array();
			foreach($cols as $tmp_id => $col) {
		        // save the PK in an array
		        if($col->isPrimaryKey()) {
		                $this->primaryKeys[$col->getColumnName()] = $col->getColumnName();
		        }

		        $value = $obj->{'get'.$col->getPhpName()}(null);
		        // save the row value

		        $row[$col->getColumnName()] = $value;
		        // save the list of propel header column name
		        $columns[$col->getColumnName()] = $col->getColumnName();
			}
			// add the row to dataset
			$dataset[] = $row;
		} // while

		$this->bind($dataset);

		if ($this->columnMode == STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS) {
			foreach($columns as $tmp_id => $column) {
				if (!$this->isColumnHidden($column)) {
					$this->addColumn(new Structures_DataGrid_Column($this->getColumnLabel($column), $column, $column, null));
				}
			}
		} else {
			foreach($this->columnVisibility as $column => $visibility) {
				if (!$this->isColumnHidden($column)) {
					// $this->addColumn(new Structures_DataGrid_Column($column, $column, $column, null));
					$this->addColumn(new Structures_DataGrid_Column($this->getColumnLabel($column), $column, $column, null));
				}
			}
		}
	} // function build

} // class Structures_DataGrid_Propel

?>
