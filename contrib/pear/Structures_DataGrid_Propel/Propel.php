<?
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
 *
 * // limit to 10 rows
 * $c = new Criteria();
 * $c->setLimit(10);
 * $dg->setCriteria($c);
 *
 * // choose what columns must be displayed
 * $dg->setColumnMode(STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS);
 * $dg->showColumn('ACTIVE');
 * $dg->showColumn('TITLE');
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
 * @author     Marc <therebel@free.fr>
 * @version    $Rev$
 * @copyright  Copyright (c) 2005 Marc: LGPL - See LICENCE
 * @package    propel.contrib
 */

class Structures_DataGrid_Propel extends Structures_DataGrid {

		/**
		 * Contains column visibility information.
		 * @var        array
		 * @access     private
		 */
		private $columnVisibility = array();

		/**
		 * The Column visibility mode.
		 * Possible values:
		 *
		 * STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS
		 * STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS
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
		 * Criteria of the Select query.
		 * @var        criteria
		 * @access     private
		 */
		private $criteria;

		/**
		 * List of primary keys
		 * @var        array
		 * @access     public
		 */
		public $primaryKeys;

		/**
		 *
		 * The Constructor
		 *
		 * Classname is specific to Structures_Datagrid_Propel
		 *
		 * The other parameters are needed to construct the parent Structures_DataGrid Class.
		 *
		 * @param      string className
		 * @param      string limit
		 * @param      string render
		 *
		 */
		public function __construct($className = null, $limit = null, $render = DATAGRID_RENDER_HTML_TABLE)
		{

				include_once $className.'.php';
				include_once $className.'Peer'.'.php';

				$this->setClassName($className);
				$this->setPeerName($className.'Peer'); // Is this always true ?
				parent::Structures_DataGrid($limit,null,$render);

				// set the default column policy
				$this->setColumnMode(STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS);
				$this->criteria = new Criteria();
		}

		/**
		 *
		 * Set the criteria for select query
		 *
		 * @param      Criteria c
		 *
		 */
		public function setCriteria(Criteria $c)
		{
				$this->criteria = $c;
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
		 *
		 * Get the visibility of a column
		 *
		 * @return     boolean true if column is set to hidden
		 *
		 */
		public function isColumnHidden($column)
		{
				if ($this->checkColumn($column, STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_HIDDEN) && $this->columnMode == STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS) {
						return true;
				}

				if (!$this->checkColumn($column, STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_VISIBLE) && $this->columnMode == STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS) {
						return true;
				}

				return false;
		}

		/**
		 *
		 * Check the state of a column
		 *
		 * @return     boolean true if column is set to state
		 *
		 */
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
		 * STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS or
		 * STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS
		 *
		 * @param      string $column column name
		 * @return     void
		 *
		 */
		public function setColumnMode($mode)
		{

				if ($mode != STRUCTURES_DATAGRID_PROPEL_NO_COLUMNS && $mode != STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS) {
						throw new PropelException('STRUCTURES_DATAGRID_PROPEL::setColumnMode(): invalid mode passed.');
				}

				$this->columnMode = $mode;
		}

		/**
		 *
		 * Tell Structures_Datagrid_Propel it should hide this column
		 * It is now passed like ID instead of somePeer::ID
		 * The latter is better, but the array_keys of the columns are
		 * in ID format and not somePeer::ID
		 *
		 * @param      string $column column name
		 * @return     void
		 *
		 */
		public function hideColumn($column)
		{
				$this->columnVisibility[$column] = STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_HIDDEN;
		}

		/**
		 *
		 * Tell Structures_Datagrid_Propel it should show this column
		 *
		 * It is now passed like ID instead of somePeer::ID
		 * The latter is better, but the array_keys of the columns are in ID format and not somePeer::ID
		 *
		 * @param      string $column column name
		 * @return     void
		 */
		public function showColumn($column)
		{
				$this->columnVisibility[$column] = STRUCTURES_DATAGRID_PROPEL_COLUMN_MADE_VISIBLE;
		}

		/**
		 *
		 * Build the datagrid
		 *
		 * @return     void
		 */
		public function build()
		{
				$mapBuilder = call_user_func(array($this->getPeerName(), 'getMapBuilder'));
				$dbMap = $mapBuilder->getDatabaseMap();
				$cols = $dbMap->getTable(constant($this->getPeerName()."::TABLE_NAME"))->getColumns();
				$stmt = call_user_func(array( $this->getPeerName(), 'doSelectStmt'), $this->criteria);

				$dataset = array();
				$columns = array();
				$this->primaryKeys = array();
				$class = $this->getClassName();
				while ($row = $stmt->fetch(PDO::FETCH_NUM)) { // use Creole ResultSet methods to iterate over resultset
						$obj = new $class();
						$obj->hydrate($row);

						$row = array();
						foreach ($cols as $tmp_id => $col)
						{
								// save the PK in an array
								if ($col->isPrimaryKey()) {
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
				}

				$this->bind($dataset);

				if ($this->columnMode == STRUCTURES_DATAGRID_PROPEL_ALL_COLUMNS) {
						foreach ($columns as $tmp_id => $column) {

								if (!$this->isColumnHidden($column)) {

								        $this->addColumn(new Structures_DataGrid_Column($column, $column, $column, null));
								}
						}
				} else {

						foreach ($this->columnVisibility as $column => $visibility) {

								if (!$this->isColumnHidden($column)) {
								        $this->addColumn(new Structures_DataGrid_Column($column, $column, $column, null));
								}
						}
				}

				$this->renderer->setTableHeaderAttributes(array('class' => 'title'));
				$this->renderer->setTableAttribute('class', 'list');
				$this->renderer->sortIconASC = '?';
				$this->renderer->sortIconDESC = '?';
		}

}
