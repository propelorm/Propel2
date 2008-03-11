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

require_once 'propel/engine/builder/om/OMBuilder.php';

/**
 * Base class for Peer-building classes.
 *
 * This class is designed so that it can be extended by a PHP4PeerBuilder in addition
 * to the "standard" PHP5PeerBuilder and PHP5ComplexOMPeerBuilder.  Hence, this class
 * should not have any actual template code in it -- simply basic logic & utility
 * methods.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder.om
 */
abstract class PeerBuilder extends OMBuilder {

	protected $basePeerClass;
	protected $basePeerClassname;

	/**
	 * Constructs a new PeerBuilder subclass.
	 */
	public function __construct(Table $table) {
		parent::__construct($table);
		$this->basePeerClassname = $this->basePeerClass = $this->getBasePeer($table);
		$pos = strrpos($this->basePeerClassname, '.');
		if ($pos !== false) {
			$this->basePeerClassname = substr($this->basePeerClassname, $pos + 1);
		}
	}

	/**
	 * Adds the addSelectColumns(), doCount(), etc. methods.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addSelectMethods(&$script)
	{
		$this->addAddSelectColumns($script);

		$this->addDoCount($script);

		// consider refactoring the doSelect stuff
		// into a top-level method
		$this->addDoSelectOne($script);
		$this->addDoSelect($script);
		$this->addDoSelectStmt($script);	 // <-- there's PDO code in here

		$this->addAddInstanceToPool($script);
		$this->addRemoveInstanceFromPool($script);
		$this->addGetInstanceFromPool($script);
		$this->addClearInstancePool($script);

		$this->addGetPrimaryKeyHash($script);
		$this->addPopulateObjects($script); // <-- there's PDO code in here

	}

	/**
	 * Adds the correct getOMClass() method, depending on whether this table uses inheritance.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addGetOMClassMethod(&$script)
	{
		$table = $this->getTable();
		if ($table->getChildrenColumn()) {
			if ($table->isAbstract()) {
				$this->addGetOMClass_Inheritance_Abstract($script);
			} else {
				$this->addGetOMClass_Inheritance($script);
			}
		} else {
			if ($table->isAbstract()) {
				$this->addGetOMClass_NoInheritance_Abstract($script);
			} else {
				$this->addGetOMClass_NoInheritance($script);
			}
		}
	}

	/**
	 * Adds the doInsert(), doUpdate(), doDeleteAll(), doValidate(), etc. methods.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addUpdateMethods(&$script)
	{
		$this->addDoInsert($script);
		$this->addDoUpdate($script);
		$this->addDoDeleteAll($script);
		$this->addDoDelete($script);
		if ($this->isDeleteCascadeEmulationNeeded()) {
			$this->addDoOnDeleteCascade($script);
		}
		if ($this->isDeleteSetNullEmulationNeeded()) {
			$this->addDoOnDeleteSetNull($script);
		}
		$this->addDoValidate($script);
	}

	/**
	 * Adds the retrieveByPK() (and possibly retrieveByPKs()) method(s) appropriate for this class.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addRetrieveByPKMethods(&$script)
	{
		if (count($this->getTable()->getPrimaryKey()) === 1) {
			$this->addRetrieveByPK_SinglePK($script);
			$this->addRetrieveByPKs_SinglePK($script);
		} else {
			$this->addRetrieveByPK_MultiPK($script);
		}
	}

	/**
	 * This method adds the contents of the generated class to the script.
	 *
	 * This method contains the high-level logic that determines which methods
	 * get generated.
	 *
	 * Hint: Override this method in your subclass if you want to reorganize or
	 * drastically change the contents of the generated peer class.
	 *
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addClassBody(&$script)
	{

		$table = $this->getTable();

		if (!$table->isAlias()) {
			$this->addConstantsAndAttributes($script);
		}

		$this->addGetMapBuilder($script);

		$this->addTranslateFieldName($script);
		$this->addGetFieldNames($script);

		if (!$table->isAlias()) {
			$this->addAlias($script); // alias() utility method (deprecated?)
			$this->addSelectMethods($script);
			$this->addGetTableMap($script);
		}

		$this->addGetOMClassMethod($script);

		// add the insert, update, delete, validate etc. methods
		if (!$table->isAlias() && !$table->isReadOnly()) {
			$this->addUpdateMethods($script);
		}

		if (count($table->getPrimaryKey()) > 0) {
			$this->addRetrieveByPKMethods($script);
		}
	}

	/**
	 * Whether the platform in use requires ON DELETE CASCADE emulation and whether there are references to this table.
	 * @return     boolean
	 */
	protected function isDeleteCascadeEmulationNeeded()
	{
		$table = $this->getTable();
		if ((!$this->getPlatform()->supportsNativeDeleteTrigger() || $this->getBuildProperty('emulateForeignKeyConstraints')) && count($table->getReferrers()) > 0) {
			foreach ($table->getReferrers() as $fk) {
				if ($fk->getOnDelete() == ForeignKey::CASCADE) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Whether the platform in use requires ON DELETE SETNULL emulation and whether there are references to this table.
	 * @return     boolean
	 */
	protected function isDeleteSetNullEmulationNeeded()
	{
		$table = $this->getTable();
		if ((!$this->getPlatform()->supportsNativeDeleteTrigger() || $this->getBuildProperty('emulateForeignKeyConstraints')) && count($table->getReferrers()) > 0) {
			foreach ($table->getReferrers() as $fk) {
				if ($fk->getOnDelete() == ForeignKey::SETNULL) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Whether to add the generic mutator methods (setByName(), setByPosition(), fromArray()).
	 * This is based on the build property propel.addGenericMutators, and also whether the
	 * table is read-only or an alias.
	 * @return     boolean
	 */
	protected function isAddGenericMutators()
	{
		$table = $this->getTable();
		return (!$table->isAlias() && $this->getBuildProperty('addGenericMutators') && !$table->isReadOnly());
	}

	/**
	 * Whether to add the generic accessor methods (getByName(), getByPosition(), toArray()).
	 * This is based on the build property propel.addGenericAccessors, and also whether the
	 * table is an alias.
	 * @return     boolean
	 */
	protected function isAddGenericAccessors()
	{
		$table = $this->getTable();
		return (!$table->isAlias() && $this->getBuildProperty('addGenericAccessors'));
	}

	/**
	 * Returns the retrieveByPK method name to use for this table.
	 * If the table is an alias then the method name looks like "retrieveTablenameByPK"
	 * otherwise simply "retrieveByPK".
	 * @return     string
	 */
	public function getRetrieveMethodName()
	{
		if ($this->getTable()->isAlias()) {
			$retrieveMethod = "retrieve" . $this->getTable()->getPhpName() . "ByPK";
		} else {
			$retrieveMethod = "retrieveByPK";
		}
		return $retrieveMethod;
	}


	/**
	 * COMPATIBILITY: Get the column constant name (e.g. PeerName::COLUMN_NAME).
	 *
	 * This method exists simply because it belonged to the 'PeerBuilder' that this
	 * class is replacing (because of name conflict more than actual functionality overlap).
	 * When the new builder model is finished this method will be removed.
	 *
	 * @param      Column $col The column we need a name for.
	 * @param      string $phpName The PHP Name of the peer class. The 'Peer' is appended automatically.
	 *
	 * @return     string If $phpName is provided, then will return {$phpName}Peer::COLUMN_NAME; if not, just COLUMN_NAME.
	 * @deprecated
	 */
	public static function getColumnName(Column $col, $phpName = null) {
		// was it overridden in schema.xml ?
		if ($col->getPeerName()) {
			$const = strtoupper($col->getPeerName());
		} else {
			$const = strtoupper($col->getName());
		}
		if ($phpName !== null) {
			return $phpName . 'Peer::' . $const;
		} else {
			return $const;
		}
	}
}
