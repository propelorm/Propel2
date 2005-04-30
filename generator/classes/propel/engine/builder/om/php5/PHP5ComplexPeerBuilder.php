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
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.om.php5
 */
class PHP5ComplexPeerBuilder extends PHP5BasicPeerBuilder {		

	/**
	 * Adds the complex OM methods to the base addSelectMethods() function.
	 * @param string &$script The script will be modified in this method.
	 * @see PeerBuilder::addSelectMethods()
	 */
	protected function addSelectMethods(&$script)
	{
		parent::addSelectMethods($script);
		$this->addDoSelectJoinSingle($script);
		
		$countFK = count($table->getForeignKeys());
		
		$includeJoinAll = true;
		
		foreach ($this->getTable()->getForeignKeys() as $fk) {
			$tblFK = $table->getDatabase()->getTable($fk->getForeignTableName());
			if ($tblFK->isForReferenceOnly()) {
			   $includeJoinAll = false;
			}
		}

		if ($includeJoinAll) {
			if($countFK > 0) {
				$this->addDoSelectJoinAll($script);
			}
			if ($countFK > 1) {
			    $this->addDoSelectJoinAllExcept($script);
			}
		}
		
	}
	
	/**
	 * Adds the doSelectJoin*() methods.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addDoSelectJoin(&$script)
	{
		 $className = $table->getPhpName();
		 $countFK = count($table->getForeignKeys());
		
		 if ($countFK >= 1) {
		 
			foreach ($table->getForeignKeys() as $fk) {
			
				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());
				
				if (!$joinTable->isForReferenceOnly()) {
				
					// FIXME - look into removing this next condition; it may not
					// be necessary:
					if ( $fk->getForeignTableName() != $table->getName() ) {
						
						// check to see if we need to add something to the method name.
						// For example if there are multiple columns that reference the same
						// table, then we have to have a methd name like doSelectJoinBooksByBookId
						$partJoinName = "";
						foreach ($fk->getLocalColumns() as $columnName ) {
							$column = $table->getColumn($columnName);
								//							this second part is not currently ever true (right?)
							if ($column->isMultipleFK() || $fk->getForeignTableName() == $table->getName()) {
								$partJoinName = $partJoinName . $column->getPhpName();
							}
						}
						
						$joinClassName = $joinTable->getPhpName();
						
						if ($joinTable->getInterface()) {
						   $interfaceName = $joinTable->getInterface();
						} else {
							$interfaceName = $joinTable->getPhpName();
						}
		
						if ($partJoinName == "") {
							$joinColumnId = $joinClassName;
							$joinInterface = $interfaceName;
							$collThisTable = $className . "s";
							$collThisTableMs = $className;
						} else {
							$joinColumnId = $joinClassName . "RelatedBy" . $partJoinName;
							$joinInterface = $interfaceName . "RelatedBy" . $partJoinName;
							$collThisTable = $className . "sRelatedBy" . $partJoinName;
							$collThisTableMs = $className . "RelatedBy" . $partJoinName;
						}
		
						$script .= "
	/**
	 * Selects a collection of $className objects pre-filled with their $joinClassName objects.
	 *
	 * @return array Array of $className objects.
	 * @throws PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function doSelectJoin$joinColumnId(Criteria \$c, \$con = null)
	{
	
		// Set the correct dbName if it has not been overridden
		if (\$c->getDbName() == Propel::getDefaultDB()) {
			\$c->setDbName(self::DATABASE_NAME);
		}
	
		".$this->getPeerClassname()."::addSelectColumns(\$c);
		\$startcol = (self::\$numColumns - self::\$numLazyLoadColumns) + 1;
		".$this->getPeerClassname($joinClassName)."::addSelectColumns(\$c);
";
		
						$lfMap = $fk->getLocalForeignMapping();
						foreach ($fk->getLocalColumns() as $columnName ) {
							$column = $table->getColumn($columnName);
							$columnFk = $joinTable->getColumn( $lfMap[$columnName]);
							$script .= "
		$c->addJoin(".$this->getColumnConstant($column).", ".$this->getColumnConstant($columnFk, $joinClassName).");";
						}
						$script .= "
		\$rs = ".$this->basePeerClassname."::doSelect(\$c, \$con);
		\$results = array();

		while(\$rs->next()) {
";
						if ($table->getChildrenColumn()) {
							$script .= "
			\$omClass = ".$this->getPeerClassname()."::getOMClass(\$rs, 1);
";
						} else {
							$script .= "
			\$omClass = ".$this->getPeerClassname()."::getOMClass();
";
						} 
						$script .= "
			\$cls = Propel::import(\$omClass);
			\$obj1 = new \$cls();
			\$obj1->hydrate(\$rs);
";
						if ($joinTable->getChildrenColumn()) {
							$script .= "
			\$omClass = ".$this->getPeerClassname($joinClassName)."::getOMClass(\$rs, \$startcol);
";
						} else { 
							$script .= "
			\$omClass = ".$this->getPeerClassname($joinClassName)."::getOMClass();
";
						}
						$script .= "
			\$cls = Propel::import(\$omClass);
			\$obj2 = new \$cls();
			\$obj2->hydrate(\$rs, \$startcol);

			\$newObject = true;
			foreach(\$results as \$temp_obj1) {
				\$temp_obj2 = \$temp_obj1->get$joinInterface();
				if (\$temp_obj2->getPrimaryKey() === \$obj2->getPrimaryKey()) {
					\$newObject = false;
					\$temp_obj2->add$collThisTableMs(\$obj1);
					break;
				}
			}
			if (\$newObject) {
				\$obj2->init$collThisTable();
				\$obj2->add$collThisTableMs(\$obj1);
			}
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
	 * Adds the doSelectJoinAll() method.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addDoSelectJoinAll(&$script)
	{

		$tblFK = $table->getDatabase()->getTable($fk->getForeignTableName());
		
		$relatedByCol = "";
		foreach ($fk->getLocalColumns() as $columnName) {
			$column = $table->getColumn($columnName);
			if ($column->isMultipleFK()) {
				$relatedByCol .= $column->getPhpName();
			}
		}
		
		if ($relatedByCol == "") {
			$collThisTable = "${className}s";
			$collThisTableMs = $className;
		} else {
			$collThisTable = $className . "sRelatedBy" . $relatedByCol;
			$collThisTableMs = $className . "RelatedBy" . $relatedByCol;
		}
		
		$script .= "

	/**
	 * Selects a collection of $className objects pre-filled with all related objects.
	 *
	 * @return array Array of $className objects.
	 * @throws PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function doSelectJoinAll(Criteria \$c, \$con = null)
	{
		// Set the correct dbName if it has not been overridden
		if (\$c->getDbName() == Propel::getDefaultDB()) {
			\$c->setDbName(self::DATABASE_NAME);
		}

		".$this->getPeerClassname()."::addSelectColumns(\$c);
		\$startcol2 = (self::\$numColumns - self::\$numLazyLoadColumns) + 1;
";
		$index = 2;
		foreach ($table->getForeignKeys() as $fk) {
			// want to cover this case, but the code is not there yet.
			// FIXME: why "is the code not there yet" ?
			if ( $fk->getForeignTableName() != $table->getName() ) {
				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());
				$joinClassName = $joinTable->getPhpName();
				$new_index = $index + 1;
				$script .= "
		".$this->getPeerClassname($joinClassName)."::addSelectColumns(\$c);
		\$startcol$new_index = \$startcol$index + ".$this->getPeerClassname($joinClassName)."::\$numColumns;
";
			$index = $new_index;
			
			} // if fk->getForeignTableName != table->getName		
		} // foreach [sub] foreign keys


		foreach ($table->getForeignKeys() as $fk) {
			// want to cover this case, but the code is not there yet.
			if ( $fk->getForeignTableName() != $table->getName() ) {
				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());
				$joinClassName = $joinTable->getPhpName();
				$lfMap = $fk->getLocalForeignMapping();
				foreach ($fk->getLocalColumns() as $columnName ) {
					$column = $table->getColumn($columnName);
					$columnFk = $joinTable->getColumn( $lfMap[$columnName]);
					$script .= "
		\$c->addJoin(".$this->getColumnConstant($column).", ".$this->getColumnConstant($columnFk, $joinClassName).");
";
	  			} 
			} 
		}
		
		$script .= "
		\$rs = ".$this->basePeerClassname."::doSelect(\$c, \$con);
		\$results = array();
		
		while(\$rs->next()) {
";

		if ($table->getChildrenColumn()) { 
			$script .= "
			\$omClass = ".$this->getPeerClassname()."::getOMClass(\$rs, 1);
";
		} else {
			$script .= "
			\$omClass = ".$this->getPeerClassname()."::getOMClass();
";
		}
	
		$script .= "
			
			\$cls = Propel::import(\$omClass);
			\$obj1 = new \$cls();
			\$obj1->hydrate(\$rs);
";

		$index = 1;
		foreach ($table->getForeignKeys() as $fk ) {
			
			// want to cover this case, but the code is not there yet.
			// FIXME -- why not?
			if ( $fk->getForeignTableName() != $table->getName() ) {
			
				$joinTable = $table->getDatabase()->getTable($fk->getForeignTableName());
				$joinClassName = $joinTable->getPhpName();
				$interfaceName = $joinTable->getPhpName();
				if($joinTable->getInterface()) {
					$interfaceName = $joinTable->getInterface();
				}
				
				$partJoinName = "";
				foreach ($fk->getLocalColumns() as $columnName ) {
					$column = $table->getColumn($columnName);
					if ($column->isMultipleFK()) {
						$partJoinName .= $column->getPhpName();
					}
				}
				
				if ($partJoinName == "") {
					$joinString = $interfaceName;
					$collThisTable = "${className}s";
					$collThisTableMs = $className;
				} else {
					$joinString= $interfaceName."RelatedBy" . $partJoinName;
					$collThisTable= $className . "sRelatedBy" . $partJoinName;
					$collThisTableMs= $className . "RelatedBy" . $partJoinName;
				}
				
				$index++;
				
				$script .= "
				
				// Add objects for joined $joinClassName rows
	";
				if ($joinTable->getChildrenColumn()) {
					$script .= "
			\$omClass = ".$this->getPeerClassname($joinClassName)."::getOMClass($rs, $startcol<?php echo $index ?>);
";
				} else {
					$script .= "
			\$omClass = ".$this->getPeerClassname($joinClassName)."::getOMClass();
";
				} /* $joinTable->getChildrenColumn() */
			
				$script .= "
	
			\$cls = Propel::import(\$omClass);
			\$obj$index = new \$cls();
			\$obj$index->hydrate(\$rs, \$startcol$index);
			
			\$newObject = true;
			for (\$j=0, \$resCount=count(\$results); \$j < \$resCount; \$j++) {
				\$temp_obj1 = \$results[\$j];
				\$temp_obj$index = \$temp_obj1->get$joinString();
				if (\$temp_obj$index->getPrimaryKey() === \$obj$index->getPrimaryKey()) {
					\$newObject = false;
					\$temp_obj$index->add$collThisTableMs(\$obj1);
					break;
				}
			}
			
			if (\$newObject) {
				\$obj$index->init$collThisTable();
				\$obj$index->add$collThisTableMs(\$obj1);
			}
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
	 * Adds the doSelectJoinAllExcept*() methods.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addDoSelectJoinAllExcept(&$script) {
	
	
		// ------------------------------------------------------------------------
		// doSelectJoinAllExcept*()
		// ------------------------------------------------------------------------
		
		// 2) create a bunch of doSelectJoinAllExcept*() methods
		// -- these were existing in original Torque, so we should keep them for compatibility
		
		$fkeys = $table->getForeignKeys();  // this sep assignment is necessary otherwise sub-loops over 
											// getForeignKeys() will cause this to only execute one time.
		foreach ($fkeys as $fk ) {
			
			$tblFK = $table->getDatabase()->getTable($fk->getForeignTableName());

			$excludeTable = $table->getDatabase()->getTable($fk->getForeignTableName());
			$excludeClassName = $excludeTable->getPhpName();

			$relatedByCol = "";
			foreach ($fk->getLocalColumns() as $columnName) {
				$column = $table->getColumn($columnName);
				if ($column->isMultipleFK()) {
					$relatedByCol .= $column->getPhpName();
				}
			}

			if ($relatedByCol == "") {
				$excludeString = $excludeClassName;
				$collThisTable = "${className}s";
				$collThisTableMs = $className;
			} else {
				$excludeString = $excludeClassName . "RelatedBy" . $relatedByCol;
				$collThisTable = $className . "sRelatedBy" . $relatedByCol;
				$collThisTableMs = $className . "RelatedBy" . $relatedByCol;
			}
			
		$script .= "
	/**
	 * Selects a collection of $className objects pre-filled with all related objects except $excludeString.
	 *
	 * @return array Array of $className objects.
	 * @throws PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function doSelectJoinAllExcept$excludeString(Criteria \$c, \$con = null)
	{
		// Set the correct dbName if it has not been overridden
		// \$c->getDbName() will return the same object if not set to another value
		// so == check is okay and faster
		if (\$c->getDbName() == Propel::getDefaultDB()) {
			\$c->setDbName(self::DATABASE_NAME);
		}

		".$this->getPeerClassname()."::addSelectColumns(\$c);
		\$startcol2 = (self::\$numColumns - self::\$numLazyLoadColumns) + 1;
";	
			$index = 2;
			foreach ($table->getForeignKeys() as $subfk) {
				// want to cover this case, but the code is not there yet.
				// FIXME - why not?
				if ( !($subfk->getForeignTableName() == $table->getName())) {
					$joinTable = $table->getDatabase()->getTable($subfk->getForeignTableName());
					$joinClassName = $joinTable->getPhpName();
		
					if ($joinClassName != $excludeClassName) {
						$new_index = $index + 1;
						$script .= "
		".$this->getPeerClassname($joinClassName)."::addSelectColumns(\$c);
		\$startcol$new_index = \$startcol$index + ".$this->getPeerClassname($joinClassName)."::\$numColumns;
";
					$index = $new_index;
					} // if joinClassName not excludeClassName 
				} // if subfk is not curr table
			} // foreach [sub] foreign keys
				
			foreach ($table->getForeignKeys() as $subfk) {
				// want to cover this case, but the code is not there yet.
				if ( $subfk->getForeignTableName() != $table->getName() ) {
					$joinTable = $table->getDatabase()->getTable($subfk->getForeignTableName());
					$joinClassName = $joinTable->getPhpName();
					if($joinClassName != $excludeClassName)
					{
						$lfMap = $subfk->getLocalForeignMapping();
						foreach ($subfk->getLocalColumns() as $columnName ) {
							$column = $table->getColumn($columnName);
							$columnFk = $joinTable->getColumn( $lfMap[$columnName]);
							$script .= "
		\$c->addJoin(".$this->getColumnConstant($column).", ".$this->getColumnConstant($columnFk, $joinClassName).");
";
						}
					} 
				}
			} // foreach fkeys 
			$script .= "

		\$rs = ".$this->basePeerClassname ."::doSelect(\$c, \$con);
		\$results = array();
		
		while(\$rs->next()) {
";
			if ($table->getChildrenColumn()) {
				$script .= "
			\$omClass = ".$this->getPeerClassname()."::getOMClass($rs, 1);
";
			} else {
				$script .= "
			\$omClass = ".$this->getPeerClassname()."::getOMClass();
";
			}
			
			$script .= "
			\$cls = Propel::import(\$omClass);
			\$obj1 = new \$cls();
			\$obj1->hydrate(\$rs);		
";
	
		$index = 1;
		foreach ($table->getForeignKeys() as $subfk ) {
		  // want to cover this case, but the code is not there yet.
		  if ( $subfk->getForeignTableName() != $table->getName() ) {
		  
				$joinTable = $table->getDatabase()->getTable($subfk->getForeignTableName());
				$joinClassName = $joinTable->getPhpName();
				$interfaceName = $joinTable->getPhpName();
				if($joinTable->getInterface()) {
					$interfaceName = $joinTable->getInterface();
				}
	
				if ($joinClassName != $excludeClassName) {
				
					$partJoinName = "";
					foreach ($subfk->getLocalColumns() as $columnName ) {
						$column = $table->getColumn($columnName);
						if ($column->isMultipleFK()) {
							$partJoinName .= $column->getPhpName();
						}
					}
	
					if ($partJoinName == "") {
						$joinString = $interfaceName;
						$collThisTable = "${className}s";
						$collThisTableMs = $className;
					} else {
						$joinString= $interfaceName."RelatedBy" . $partJoinName;
						$collThisTable= $className . "sRelatedBy" . $partJoinName;
						$collThisTableMs= $className . "RelatedBy" . $partJoinName;
					}
	
					$index++;
				
					if ($joinTable->getChildrenColumn()) {
						$script .= "
			\$omClass = ".$this->getPeerClassname($joinClassName)."::getOMClass(\$rs, \$startcol$index);
";
					} else {
						$script .= "
			\$omClass = ".$this->getPeerClassname($joinClassName)."::getOMClass();
";
					} /* $joinTable->getChildrenColumn() */
	
					$script .= "
	
			\$cls = Propel::import(\$omClass);
			\$obj$index  = new \$cls();
			\$obj$index->hydrate(\$rs, \$startcol$index);
			
			\$newObject = true;
			for (\$j=0, \$resCount=count(\$results); \$j < \$resCount; \$j++) {
				\$temp_obj1 = \$results[\$j];
				\$temp_obj$index = \$temp_obj1->get$joinString();
				if (\$temp_obj$index->getPrimaryKey() === \$obj$index->getPrimaryKey()) {
					\$newObject = false;
					\$temp_obj$index->add$collThisTableMs(\$obj1);
					break;
				}
			}
			
			if (\$newObject) {
				\$obj$index->init$collThisTable();
				\$obj$index->add$collThisTableMs(\$obj1);
			}
";
					} // if ($joinClassName != $excludeClassName) {
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
	
} // PHP5ComplexPeerBuilder
