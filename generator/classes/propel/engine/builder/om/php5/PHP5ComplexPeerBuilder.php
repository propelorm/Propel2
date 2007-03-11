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

require_once 'propel/engine/builder/om/php5/PHP5BasicPeerBuilder.php';

/**
 * Generates a PHP5 base Peer class with complex object model methods.
 *
 * This class extends the basic peer builder by adding on the doSelectJoin*()
 * methods and other complex object model methods.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder.om.php5
 */
class PHP5ComplexPeerBuilder extends PHP5BasicPeerBuilder {

	/**
	 * Adds the complex OM methods to the base addSelectMethods() function.
	 * @param      string &$script The script will be modified in this method.
	 * @see        PeerBuilder::addSelectMethods()
	 */
	protected function addSelectMethods(&$script)
	{
		$table = $this->getTable();

		parent::addSelectMethods($script);

		$this->addDoCountJoin($script);
		$this->addDoSelectJoin($script);

		$countFK = count($table->getForeignKeys());

		$includeJoinAll = true;

		foreach ($this->getTable()->getForeignKeys() as $fk) {
			$tblFK = $table->getDatabase()->getTable($fk->getForeignTableName());
			if ($tblFK->isForReferenceOnly()) {
			   $includeJoinAll = false;
			}
		}

		if ($includeJoinAll) {
			if ($countFK > 0) {
				$this->addDoCountJoinAll($script);
				$this->addDoSelectJoinAll($script);
			}
			if ($countFK > 1) {
				$this->addDoCountJoinAllExcept($script);
				$this->addDoSelectJoinAllExcept($script);
			}
		}

	}

	/**
	 * Adds the doSelectJoin*() methods.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addDoSelectJoin(&$script)
	{
		$table = $this->getTable();
		$className = $this->getObjectClassname();
		$countFK = count($table->getForeignKeys());

		if ($countFK >= 1) {

			foreach ($table->getForeignKeys() as $fk) {

				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());

				if (!$joinTable->isForReferenceOnly()) {

					// FIXME - look into removing this next condition; it may not
					// be necessary:
					// --- IT is necessary because there needs to be a system for
					// aliasing the table if it is the same table.
					if ( $fk->getForeignTableName() != $table->getName() ) {

						$thisTableObjectBuilder = OMBuilder::getNewObjectBuilder($table);
						$joinedTableObjectBuilder = OMBuilder::getNewObjectBuilder($joinTable);
						$joinedTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);

						$joinClassName = $joinedTableObjectBuilder->getObjectClassname();

						$script .= "

	/**
	 * Selects a collection of $className objects pre-filled with their $joinClassName objects.
	 *
	 * @return     array Array of $className objects.
	 * @throws     PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function doSelectJoin".$thisTableObjectBuilder->getFKPhpNameAffix($fk, $plural = false)."(Criteria \$c, \$con = null)
	{
		\$c = clone \$c;

		// Set the correct dbName if it has not been overridden
		if (\$c->getDbName() == Propel::getDefaultDB()) {
			\$c->setDbName(self::DATABASE_NAME);
		}

		".$this->getPeerClassname()."::addSelectColumns(\$c);
		\$startcol = (".$this->getPeerClassname()."::NUM_COLUMNS - ".$this->getPeerClassname()."::NUM_LAZY_LOAD_COLUMNS);
		".$joinedTablePeerBuilder->getPeerClassname()."::addSelectColumns(\$c);
";

						$lfMap = $fk->getLocalForeignMapping();
						foreach ($fk->getLocalColumns() as $columnName ) {
							$column = $table->getColumn($columnName);
							$columnFk = $joinTable->getColumn( $lfMap[$columnName] );
							$script .= "
		\$c->addJoin(".$this->getColumnConstant($column).", ".$joinedTablePeerBuilder->getColumnConstant($columnFk).");"; //CHECKME
						}
						$script .= "
		\$stmt = ".$this->basePeerClassname."::doSelect(\$c, \$con);
		\$results = array();

		while (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
			\$key1 = ".$this->getPeerClassname()."::getPrimaryKeyHashFromRow(\$row, 0);
			if (isset(self::\$instances[\$key1])) {
				\$obj1 = self::\$instances[\$key1];
				\$obj1->hydrate(\$row, 0, true); // rehydrate
			} else {
";
						if ($table->getChildrenColumn()) {
							$script .= "
				\$omClass = ".$this->getPeerClassname()."::getOMClass(\$row, 0);
";
						} else {
							$script .= "
				\$omClass = ".$this->getPeerClassname()."::getOMClass();
";
						}
						$script .= "
				\$cls = substr('.'.\$omClass, strrpos('.'.\$omClass, '.') + 1);
				" . $this->buildObjectInstanceCreationCode('$obj1', '$cls') . "
				\$obj1->hydrate(\$row);
				self::\$instances[\$key1] = \$obj1;
			} // if \$obj1 already loaded

			\$key2 = ".$joinedTablePeerBuilder->getPeerClassname()."::getPrimaryKeyHashFromRow(\$row, \$startcol);
			\$obj2 = ".$joinedTablePeerBuilder->getPeerClassname()."::getInstanceFromPool(\$key2);
			if (!\$obj2) {
";
						if ($joinTable->getChildrenColumn()) {
							$script .= "
				\$omClass = ".$joinedTablePeerBuilder->getPeerClassname()."::getOMClass(\$row, \$startcol);
";
						} else {
							$script .= "
				\$omClass = ".$joinedTablePeerBuilder->getPeerClassname()."::getOMClass();
";
						}

						$script .= "
				\$cls = substr('.'.\$omClass, strrpos('.'.\$omClass, '.') + 1);
				" . $this->buildObjectInstanceCreationCode('$obj2', '$cls') . "
				\$obj2->hydrate(\$row, \$startcol);
				".$joinedTablePeerBuilder->getPeerClassname()."::addInstanceToPool(\$obj2); // FIXME, we should optimize this since we already calculated the key above
			} // if obj2 already loaded

			// Add the \$obj1 (".$this->getObjectClassname().") to the collection in \$obj2 (".$joinedTablePeerBuilder->getObjectClassname().")
			\$obj2->add".$joinedTableObjectBuilder->getRefFKPhpNameAffix($fk, $plural = false)."(\$obj1);

			\$results[] = \$obj1;
		}
		return \$results;
	}
";
					} // if fk table name != this table name
				} // if ! is reference only
			} // foreach column
		} // if count(fk) > 1

	} // addDoSelectJoin()

	/**
	 * Adds the doCountJoin*() methods.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addDoCountJoin(&$script)
	{
		$table = $this->getTable();
		$className = $this->getObjectClassname();
		$countFK = count($table->getForeignKeys());

		if ($countFK >= 1) {

			foreach ($table->getForeignKeys() as $fk) {

				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());

				if (!$joinTable->isForReferenceOnly()) {

					if ( $fk->getForeignTableName() != $table->getName() ) {

						$thisTableObjectBuilder = OMBuilder::getNewObjectBuilder($table);
						$joinedTableObjectBuilder = OMBuilder::getNewObjectBuilder($joinTable);
						$joinedTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);

						$joinClassName = $joinedTableObjectBuilder->getObjectClassname();

						$script .= "

	/**
	 * Returns the number of rows matching criteria, joining the related ".$thisTableObjectBuilder->getFKPhpNameAffix($fk, $plural = false)." table
	 *
	 * @param      Criteria \$c
	 * @param      boolean \$distinct Whether to select only distinct columns (You can also set DISTINCT modifier in Criteria).
	 * @param      PDO \$con
	 * @return     int Number of matching rows.
	 */
	public static function doCountJoin".$thisTableObjectBuilder->getFKPhpNameAffix($fk, $plural = false)."(Criteria \$criteria, \$distinct = false, PDO \$con = null)
	{
		// we're going to modify criteria, so copy it first
		\$criteria = clone \$criteria;

		// clear out anything that might confuse the ORDER BY clause
		\$criteria->clearSelectColumns()->clearOrderByColumns();
		if (\$distinct || in_array(Criteria::DISTINCT, \$criteria->getSelectModifiers())) {
			\$criteria->addSelectColumn(".$this->getPeerClassname()."::COUNT_DISTINCT);
		} else {
			\$criteria->addSelectColumn(".$this->getPeerClassname()."::COUNT);
		}

		// just in case we're grouping: add those columns to the select statement
		foreach (\$criteria->getGroupByColumns() as \$column)
		{
			\$criteria->addSelectColumn(\$column);
		}
";
						$lfMap = $fk->getLocalForeignMapping();
						foreach ($fk->getLocalColumns() as $columnName ) {
							$column = $table->getColumn($columnName);
							$columnFk = $joinTable->getColumn( $lfMap[$columnName] );
							$script .= "
		\$criteria->addJoin(".$this->getColumnConstant($column).", ".$joinedTablePeerBuilder->getColumnConstant($columnFk).");
";
						}
						$script .= "
		\$stmt = ".$this->getPeerClassname()."::doSelectStmt(\$criteria, \$con);
		if (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
			return (int) \$row[0];
		} else {
			// no rows returned; we infer that means 0 matches.
			return 0;
		}
	}
";
					} // if fk table name != this table name
				} // if ! is reference only
			} // foreach column
		} // if count(fk) > 1

	} // addDoCountJoin()

	/**
	 * Adds the doSelectJoinAll() method.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addDoSelectJoinAll(&$script)
	{
		$table = $this->getTable();
		$className = $this->getObjectClassname();

		$script .= "

	/**
	 * Selects a collection of $className objects pre-filled with all related objects.
	 *
	 * @return     array Array of $className objects.
	 * @throws     PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function doSelectJoinAll(Criteria \$c, \$con = null)
	{
		\$c = clone \$c;

		// Set the correct dbName if it has not been overridden
		if (\$c->getDbName() == Propel::getDefaultDB()) {
			\$c->setDbName(self::DATABASE_NAME);
		}

		".$this->getPeerClassname()."::addSelectColumns(\$c);
		\$startcol2 = (".$this->getPeerClassname()."::NUM_COLUMNS - ".$this->getPeerClassname()."::NUM_LAZY_LOAD_COLUMNS);
";
		$index = 2;
		foreach ($table->getForeignKeys() as $fk) {
			// want to cover this case, but the code is not there yet.
			// FIXME: why "is the code not there yet" ?
			if ( $fk->getForeignTableName() != $table->getName() ) {
				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());
				$new_index = $index + 1;

				$joinedTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);
				$joinClassName = $joinedTablePeerBuilder->getObjectClassname();

				$script .= "
		".$joinedTablePeerBuilder->getPeerClassname()."::addSelectColumns(\$c);
		\$startcol$new_index = \$startcol$index + ".$joinedTablePeerBuilder->getPeerClassname()."::NUM_COLUMNS;
";
			$index = $new_index;

			} // if fk->getForeignTableName != table->getName
		} // foreach [sub] foreign keys

		foreach ($table->getForeignKeys() as $fk) {
			// want to cover this case, but the code is not there yet.
			if ( $fk->getForeignTableName() != $table->getName() ) {
				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());
				$joinedTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);

				$joinClassName = $joinedTablePeerBuilder->getObjectClassname();
				$lfMap = $fk->getLocalForeignMapping();
				foreach ($fk->getLocalColumns() as $columnName ) {
					$column = $table->getColumn($columnName);
					$columnFk = $joinTable->getColumn( $lfMap[$columnName]);
					$script .= "
		\$c->addJoin(".$this->getColumnConstant($column).", ".$joinedTablePeerBuilder->getColumnConstant($columnFk).");
";
	  			}
			}
		}

		$script .= "
		\$stmt = ".$this->basePeerClassname."::doSelect(\$c, \$con);
		\$results = array();

		while (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
			\$key1 = ".$this->getPeerClassname()."::getPrimaryKeyHashFromRow(\$row, 0);
			if (isset(self::\$instances[\$key1])) {
				\$obj1 = self::\$instances[\$key1];
				\$obj1->hydrate(\$row, 0, true); // rehydrate
			} else {
";

		if ($table->getChildrenColumn()) {
			$script .= "
				\$omClass = ".$this->getPeerClassname()."::getOMClass(\$row, 0);
";
		} else {
			$script .= "
				\$omClass = ".$this->getPeerClassname()."::getOMClass();
";
		}

		$script .= "
				\$cls = substr('.'.\$omClass, strrpos('.'.\$omClass, '.') + 1);
				" . $this->buildObjectInstanceCreationCode('$obj1', '$cls') . "
				\$obj1->hydrate(\$row);
				self::\$instances[\$key1] = \$obj1;
			} // if obj1 already loaded
";

		$index = 1;
		foreach ($table->getForeignKeys() as $fk ) {
			// want to cover this case, but the code is not there yet.
			// FIXME -- why not? -because we'd have to alias the tables in the JOIN
			if ( $fk->getForeignTableName() != $table->getName() ) {
				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());
			    
				$thisTableObjectBuilder = OMBuilder::getNewObjectBuilder($table);
				$joinedTableObjectBuilder = OMBuilder::getNewObjectBuilder($joinTable);
				$joinedTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);


				$joinClassName = $joinedTableObjectBuilder->getObjectClassname();
				$interfaceName = $joinClassName;

				if ($joinTable->getInterface()) {
					$interfaceName = DataModelPeer::prefixClassname($joinTable->getInterface());
				}

				$index++;

				$script .= "
			// Add objects for joined $joinClassName rows

			\$key$index = ".$joinedTablePeerBuilder->getPeerClassname()."::getPrimaryKeyHashFromRow(\$row, \$startcol$index);

			\$obj$index = ".$joinedTablePeerBuilder->getPeerClassname()."::getInstanceFromPool(\$key$index);
			if (!\$obj$index) {
";
				if ($joinTable->getChildrenColumn()) {
					$script .= "
				\$omClass = ".$joinedTablePeerBuilder->getPeerClassname()."::getOMClass(\$row, \$startcol$index);
";
				} else {
					$script .= "
				\$omClass = ".$joinedTablePeerBuilder->getPeerClassname()."::getOMClass();
";
				} /* $joinTable->getChildrenColumn() */

				$script .= "

				\$cls = substr('.'.\$omClass, strrpos('.'.\$omClass, '.') + 1);
				" . $this->buildObjectInstanceCreationCode('$obj' . $index, '$cls') . "
				\$obj".$index."->hydrate(\$row, \$startcol$index);
				".$joinedTablePeerBuilder->getPeerClassname()."::addInstanceToPool(\$obj$index); // FIXME - Optimize: we already know the key
			} // if obj$index loaded

			// Add the \$obj1 (".$this->getObjectClassname().") to the collection in \$obj".$index." (".$joinedTablePeerBuilder->getObjectClassname().")
			\$obj".$index."->add".$joinedTableObjectBuilder->getRefFKPhpNameAffix($fk, $plural = false)."(\$obj1);
";

			} // $fk->getForeignTableName() != $table->getName()
		} //foreach foreign key

		$script .= "
			\$results[] = \$obj1;
		}
		return \$results;
	}
";

	} // end addDoSelectJoinAll()

	/**
	 * Adds the doCountJoinAll() method.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addDoCountJoinAll(&$script)
	{
		$table = $this->getTable();
		$className = $this->getObjectClassname();

		$script .= "

	/**
	 * Returns the number of rows matching criteria, joining all related tables
	 *
	 * @param      Criteria \$c
	 * @param      boolean \$distinct Whether to select only distinct columns (You can also set DISTINCT modifier in Criteria).
	 * @param      PDO \$con
	 * @return     int Number of matching rows.
	 */
	public static function doCountJoinAll(Criteria \$criteria, \$distinct = false, PDO \$con = null)
	{
		\$criteria = clone \$criteria;

		// clear out anything that might confuse the ORDER BY clause
		\$criteria->clearSelectColumns()->clearOrderByColumns();
		if (\$distinct || in_array(Criteria::DISTINCT, \$criteria->getSelectModifiers())) {
			\$criteria->addSelectColumn(".$this->getPeerClassname()."::COUNT_DISTINCT);
		} else {
			\$criteria->addSelectColumn(".$this->getPeerClassname()."::COUNT);
		}

		// just in case we're grouping: add those columns to the select statement
		foreach (\$criteria->getGroupByColumns() as \$column)
		{
			\$criteria->addSelectColumn(\$column);
		}
";

		foreach ($table->getForeignKeys() as $fk) {
			// want to cover this case, but the code is not there yet.
			if ( $fk->getForeignTableName() != $table->getName() ) {
				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());
				$joinedTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);

				$joinClassName = $joinedTablePeerBuilder->getObjectClassname();

				$lfMap = $fk->getLocalForeignMapping();
				foreach ($fk->getLocalColumns() as $columnName ) {
					$column = $table->getColumn($columnName);
					$columnFk = $joinTable->getColumn( $lfMap[$columnName]);
					$script .= "
		\$criteria->addJoin(".$this->getColumnConstant($column).", ".$joinedTablePeerBuilder->getColumnConstant($columnFk).");
";
				}
			} // if fk->getForeignTableName != table->getName
		} // foreach [sub] foreign keys

		$script .= "
		\$stmt = ".$this->getPeerClassname()."::doSelectStmt(\$criteria, \$con);
		if (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
			return (int) \$row[0];
		} else {
			// no rows returned; we infer that means 0 matches.
			return 0;
		}
	}
";
	} // end addDoCountJoinAll()

	/**
	 * Adds the doSelectJoinAllExcept*() methods.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addDoSelectJoinAllExcept(&$script)
	{
		$table = $this->getTable();

		// ------------------------------------------------------------------------
		// doSelectJoinAllExcept*()
		// ------------------------------------------------------------------------

		// 2) create a bunch of doSelectJoinAllExcept*() methods
		// -- these were existing in original Torque, so we should keep them for compatibility

		$fkeys = $table->getForeignKeys();  // this sep assignment is necessary otherwise sub-loops over
											// getForeignKeys() will cause this to only execute one time.
		foreach ($fkeys as $fk ) {

			$tblFK = $table->getDatabase()->getTable($fk->getForeignTableName());

			$excludedTable = $table->getDatabase()->getTable($fk->getForeignTableName());

			$thisTableObjectBuilder = OMBuilder::getNewObjectBuilder($table);
			$excludedTableObjectBuilder = OMBuilder::getNewObjectBuilder($excludedTable);
			$excludedTablePeerBuilder = OMBuilder::getNewPeerBuilder($excludedTable);

			$excludedClassName = $excludedTableObjectBuilder->getObjectClassname();


		$script .= "

	/**
	 * Selects a collection of ".$this->getObjectClassname()." objects pre-filled with all related objects except ".$thisTableObjectBuilder->getFKPhpNameAffix($fk).".
	 *
	 * @return     array Array of ".$this->getObjectClassname()." objects.
	 * @throws     PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function doSelectJoinAllExcept".$thisTableObjectBuilder->getFKPhpNameAffix($fk, $plural = false)."(Criteria \$c, \$con = null)
	{
		\$c = clone \$c;

		// Set the correct dbName if it has not been overridden
		// \$c->getDbName() will return the same object if not set to another value
		// so == check is okay and faster
		if (\$c->getDbName() == Propel::getDefaultDB()) {
			\$c->setDbName(self::DATABASE_NAME);
		}

		".$this->getPeerClassname()."::addSelectColumns(\$c);
		\$startcol2 = (".$this->getPeerClassname()."::NUM_COLUMNS - ".$this->getPeerClassname()."::NUM_LAZY_LOAD_COLUMNS);
";
			$index = 2;
			foreach ($table->getForeignKeys() as $subfk) {
				// want to cover this case, but the code is not there yet.
				// FIXME - why not?
				if ( !($subfk->getForeignTableName() == $table->getName())) {
					$joinTable = $table->getDatabase()->getTable($subfk->getForeignTableName());
					$joinTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);
					$joinClassName = $joinTablePeerBuilder->getObjectClassname();

					if ($joinClassName != $excludedClassName) {
						$new_index = $index + 1;
						$script .= "
		".$joinTablePeerBuilder->getPeerClassname()."::addSelectColumns(\$c);
		\$startcol$new_index = \$startcol$index + ".$joinTablePeerBuilder->getPeerClassname()."::NUM_COLUMNS;
";
					$index = $new_index;
					} // if joinClassName not excludeClassName
				} // if subfk is not curr table
			} // foreach [sub] foreign keys

			foreach ($table->getForeignKeys() as $subfk) {
				// want to cover this case, but the code is not there yet.
				if ( $subfk->getForeignTableName() != $table->getName() ) {
					$joinTable = $table->getDatabase()->getTable($subfk->getForeignTableName());
					$joinTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);
					$joinClassName = $joinTablePeerBuilder->getObjectClassname();

					if ($joinClassName != $excludedClassName)
					{
						$lfMap = $subfk->getLocalForeignMapping();
						foreach ($subfk->getLocalColumns() as $columnName ) {
							$column = $table->getColumn($columnName);
							$columnFk = $joinTable->getColumn( $lfMap[$columnName]);
							$script .= "
		\$c->addJoin(".$this->getColumnConstant($column).", ".$joinTablePeerBuilder->getColumnConstant($columnFk).");
";
						}
					}
				}
			} // foreach fkeys
			$script .= "

		\$stmt = ".$this->basePeerClassname ."::doSelect(\$c, \$con);
		\$results = array();

		while (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
			\$key1 = ".$this->getPeerClassname()."::getPrimaryKeyHashFromRow(\$row, 0);
			if (isset(self::\$instances[\$key1])) {
				\$obj1 = self::\$instances[\$key1];
				\$obj1->hydrate(\$row, 0, true); // rehydrate
			} else {
";
			if ($table->getChildrenColumn()) {
				$script .= "
				\$omClass = ".$this->getPeerClassname()."::getOMClass(\$row, 0);
";
			} else {
				$script .= "
				\$omClass = ".$this->getPeerClassname()."::getOMClass();
";
			}

			$script .= "
				\$cls = substr('.'.\$omClass, strrpos('.'.\$omClass, '.') + 1);
				" . $this->buildObjectInstanceCreationCode('$obj1', '$cls') . "
				\$obj1->hydrate(\$row);
				// print \"->Adding \" . get_class(\$obj1) . \" \" . \$obj1 . \" into instance pool.\\n\";
				self::\$instances[\$key1] = \$obj1;
			} // if obj1 already loaded
";

		$index = 1;
		foreach ($table->getForeignKeys() as $subfk ) {
		  // want to cover this case, but the code is not there yet.
		  if ( $subfk->getForeignTableName() != $table->getName() ) {

				$joinTable = $table->getDatabase()->getTable($subfk->getForeignTableName());

				$joinedTableObjectBuilder = OMBuilder::getNewObjectBuilder($joinTable);
				$joinedTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);

				$joinClassName = $joinedTableObjectBuilder->getObjectClassname();

				$interfaceName = $joinClassName;
				if ($joinTable->getInterface()) {
					$interfaceName = DataModelBuilder::prefixClassname($joinTable->getInterface());
				}

				if ($joinClassName != $excludedClassName) {

					$index++;

					$script .= "
				// Add objects for joined $joinClassName rows

				\$key$index = ".$joinedTablePeerBuilder->getPeerClassname()."::getPrimaryKeyHashFromRow(\$row, \$startcol$index);
				\$obj$index = ".$joinedTablePeerBuilder->getPeerClassname()."::getInstanceFromPool(\$key$index);
				if (!\$obj$index) {
	";

					if ($joinTable->getChildrenColumn()) {
						$script .= "
					\$omClass = ".$joinedTablePeerBuilder->getPeerClassname()."::getOMClass(\$row, \$startcol$index);
";
					} else {
						$script .= "
					\$omClass = ".$joinedTablePeerBuilder->getPeerClassname()."::getOMClass();
";
					} /* $joinTable->getChildrenColumn() */
					$script .= "

				\$cls = substr('.'.\$omClass, strrpos('.'.\$omClass, '.') + 1);
				" . $this->buildObjectInstanceCreationCode('$obj' . $index, '$cls') . "
				\$obj".$index."->hydrate(\$row, \$startcol$index);
				".$joinedTablePeerBuilder->getPeerClassname()."::addInstanceToPool(\$obj$index); // FIXME - Optimize: we already calculated the key
			} // if \$obj$index already loaded

			// Add the \$obj1 (".$this->getObjectClassname().") to the collection in \$obj".$index." (".$joinedTablePeerBuilder->getObjectClassname().")
			\$obj".$index."->add".$joinedTableObjectBuilder->getRefFKPhpNameAffix($fk, $plural = false)."(\$obj1);
";
					} // if ($joinClassName != $excludedClassName) {
			} // $subfk->getForeignTableName() != $table->getName()
		} // foreach
		$script .= "
			\$results[] = \$obj1;
		}
		return \$results;
	}
";
		} // foreach fk

	} // addDoSelectJoinAllExcept

	/**
	 * Adds the doCountJoinAllExcept*() methods.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addDoCountJoinAllExcept(&$script)
	{
		$table = $this->getTable();

		$fkeys = $table->getForeignKeys();  // this sep assignment is necessary otherwise sub-loops over
											// getForeignKeys() will cause this to only execute one time.
		foreach ($fkeys as $fk ) {

			$tblFK = $table->getDatabase()->getTable($fk->getForeignTableName());

			$excludedTable = $table->getDatabase()->getTable($fk->getForeignTableName());

			$thisTableObjectBuilder = OMBuilder::getNewObjectBuilder($table);
			$excludedTableObjectBuilder = OMBuilder::getNewObjectBuilder($excludedTable);
			$excludedTablePeerBuilder = OMBuilder::getNewPeerBuilder($excludedTable);

			$excludedClassName = $excludedTableObjectBuilder->getObjectClassname();

		$script .= "

	/**
	 * Returns the number of rows matching criteria, joining the related ".$thisTableObjectBuilder->getFKPhpNameAffix($fk, $plural = false)." table
	 *
	 * @param      Criteria \$c
	 * @param      boolean \$distinct Whether to select only distinct columns (You can also set DISTINCT modifier in Criteria).
	 * @param      PDO \$con
	 * @return     int Number of matching rows.
	 */
	public static function doCountJoinAllExcept".$thisTableObjectBuilder->getFKPhpNameAffix($fk, $plural = false)."(Criteria \$criteria, \$distinct = false, PDO \$con = null)
	{
		// we're going to modify criteria, so copy it first
		\$criteria = clone \$criteria;

		// clear out anything that might confuse the ORDER BY clause
		\$criteria->clearSelectColumns()->clearOrderByColumns();
		if (\$distinct || in_array(Criteria::DISTINCT, \$criteria->getSelectModifiers())) {
			\$criteria->addSelectColumn(".$this->getPeerClassname()."::COUNT_DISTINCT);
		} else {
			\$criteria->addSelectColumn(".$this->getPeerClassname()."::COUNT);
		}

		// just in case we're grouping: add those columns to the select statement
		foreach (\$criteria->getGroupByColumns() as \$column)
		{
			\$criteria->addSelectColumn(\$column);
		}
";

			foreach ($table->getForeignKeys() as $subfk) {
				// want to cover this case, but the code is not there yet.
				if ( $subfk->getForeignTableName() != $table->getName() ) {
					$joinTable = $table->getDatabase()->getTable($subfk->getForeignTableName());
					$joinTablePeerBuilder = OMBuilder::getNewPeerBuilder($joinTable);
					$joinClassName = $joinTablePeerBuilder->getObjectClassname();

					if ($joinClassName != $excludedClassName)
					{
						$lfMap = $subfk->getLocalForeignMapping();
						foreach ($subfk->getLocalColumns() as $columnName ) {
							$column = $table->getColumn($columnName);
							$columnFk = $joinTable->getColumn( $lfMap[$columnName]);
							$script .= "
		\$criteria->addJoin(".$this->getColumnConstant($column).", ".$joinTablePeerBuilder->getColumnConstant($columnFk).");
";
						}
					}
				}
			} // foreach fkeys
			$script .= "
		\$stmt = ".$this->getPeerClassname()."::doSelectStmt(\$criteria, \$con);
		if (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
			return (int) \$row[0];
		} else {
			// no rows returned; we infer that means 0 matches.
			return 0;
		}
	}
";
		} // foreach fk

	} // addDoCountJoinAllExcept

} // PHP5ComplexPeerBuilder
