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
	}
	
	private function addDoSelectJoinSingle(&$script)
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
		
	} // addDoSelectJoinSingle()
		
} // PHP5ComplexPeerBuilder
