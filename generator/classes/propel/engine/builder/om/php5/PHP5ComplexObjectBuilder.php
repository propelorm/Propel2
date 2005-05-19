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

require_once 'propel/engine/builder/om/php5/PHP5BasicObjectBuilder.php';

/**
 * Generates a PHP5 base Object class with complex object model methods.
 * 
 * This class adds on to the PHP5BasicObjectBuilder class by adding more complex
 * logic related to relationships to methods like the setters, and save method. Also,
 * new get*Join*() methods are added to fetch related rows.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.om.php5
 */
class PHP5ComplexObjectBuilder extends PHP5BasicObjectBuilder {		
	
	/**
	 * Adds additional attributes used for complex object model.
	 */
	protected function addAttributes(&$script)
	{
		parent::addAttributes($script);
		foreach ($table->getForeignKeys() as $fk) {
			$this->addFKAttributes($script, $fk);
		}
		$this->addAlreadyInSaveAttribute($script);
		$this->addAlreadyInValidationAttribute($script);
	}
	
	/**
	 * 
	 * @param string &$script The script will be modified in this method.
	 * @param Column $col The current column.
	 */
	protected function addMutatorClose(&$script, $col)
	{
		$cfc = $col->getPhpName();
		$script .= "
	} // set$cfc()
";
	$script .= "

			if ($col->isForeignKey()) {
				$tblFK = $table->getDatabase()->getTable($col->getRelatedTableName());
				$colFK = $tblFK->getColumn($col->getRelatedColumnName());
				if ($col->isMultipleFK() || $col->getRelatedTableName() == $table->getName()) {
					$relCol = "";
					foreach ($col->getForeignKey()->getLocalColumns() as $columnName) {
						$column = $table->getColumn($columnName);
						$relCol .= $column->getPhpName();
					}
					if ($relCol != "") {
						$relCol = "RelatedBy".$relCol;
					}
					$varName = "a".$tblFK->getPhpName() . $relCol;
				} else {
					$varName = "a".$tblFK->getPhpName();
				}

	?>

		if ($this-><?php echo $varName ?> !== null && $this-><?php echo $varName ?>->get<?php echo $colFK->getPhpName() ?>() !== $v) {
			$this-><?php echo $varName ?> = null;
		}
	<?php	 } /* if col is foreign key */

		foreach ($col->getReferrers() as $fk) {
			// used to be getLocalForeignMapping() which did not work.
			$flmap = $fk->getForeignLocalMapping();
			$fkColName = $flmap[$col->getName()];
			$tblFK = $fk->getTable();
			if ( $tblFK->getName() != $table->getName() ) {
				$colFK = $tblFK->getColumn($fkColName);
				if ($colFK->isMultipleFK()) {
					$collName = "coll" . $tblFK->getPhpName() . "sRelatedBy" . $colFK->getPhpName();
				} else {
					$collName = "coll" . $tblFK->getPhpName() . "s";
				}
	?>

		  // update associated <?php echo $tblFK->getPhpName() ?>

		  if ($this-><?php echo $collName ?> !== null) {
			  for ($i=0,$size=count($this-><?php echo $collName ?>); $i < $size; $i++) {
				  $this-><?php echo $collName ?>[$i]->set<?php echo $colFK->getPhpName()?>($v);
			  }
		  }
		<?php } /* if  $tblFk != $table */ ?>
	  <?php } /* foreach referrers */ ?>

";

	}
	
	/**
	 * Gets the Table object for the foreign key.
	 * @return string
	 */
	protected function getFKTable(ForeignKey $fk)
	{
		return $this->getTable()->getDatabase()->getTable($fk->getForeignTableName());
	}
	
	/**
	 * Returns the PHP class name (phpName) for the foreign key table.
	 * @return string
	 */
	protected function getFKClassname(ForeignKey $fk)
	{
		$tblFK = $this->getTable()->getDatabase()->getTable($fk->getForeignTableName());
		return $tblFK->getPhpName();
	}
	
	/**
	 * Gets the PHP method name affix to be used for methods and variable names (e.g. set????(), $coll???).
	 * @return string
	 */
	protected function getPhpNameAffix(ForeignKey $fk, $plural = false)
	{
		$tblFK = $this->getFKTable($fk);
		$className = $tblFK->getPhpName();				
		return $className . ($plural ? 's' : '') . $this->getRelatedBySuffix($fk);
	}
	
	/**
	 * Gets the "RelatedBy*" suffix (if needed) that is attached to method and variable names.
	 * @return string
	 */
	protected function getRelatedBySuffix(ForeignKey $fk)
	{
		$relCol = "";
		foreach ($fk->getLocalColumns() as $columnName) {
			$column = $table->getColumn($columnName);
			if ($column->isMultipleFK() || $fk->getForeignTableName() == $table->getName()) {
				// if there are seeral foreign keys that point to the same table
				// then we need to generate methods like getAuthorRelatedByColName()
				// instead of just getAuthor().  Currently we are doing the same
				// for self-referential foreign keys, to avoid confusion.
				$relCol .= $column->getPhpName();
			}
		}

		if ($relCol != "") {
			$relCol = "RelatedBy" . $relCol;
		}
		
		return $relCol;
	}

	// TODO / FIXME
	// copy above method into several copies which will return the various
	// pieces needed for the more advnaced fk stuff (relCols, relColsMs, etc.).
	//
	// OR: add $plural param to that method & rename the method something more generic
	// getPhpName() or something ...
	
	protected function getFKVarName(ForeignKey $fk)
	{
		return 'a' . $this->getPhpNameAffix($fk, $plural = false);
	}
	
	protected function getRefFKCollVarName(ForeignKey $fk)
	{
		return 'coll' . $this->getPhpNameAffix($fk, $plural = true);
	}
	
	protected function getRefFKLastCriteriaVarName(ForeignKey $fk)
	{
		return 'last' . $this->getPhpNameAffix($fk, $plural = false) . 'Criteria';
	}
	
	protected function addFKMethods(&$script)
	{
	
		foreach ($table->getForeignKeys() as $fk) {
					
			//$this->addFkeyAttributes($script, $fk);
			$this->addFKMutator($script, $fk);
			$this->addFKAccessor($script, $fk);
						
		} // foreach fk
	
	}
	
	protected function addFKAttributes(&$script, ForeignKey $fk)
	{
		$className = $this->getFKClassname($fk);
		$varName = $this->getFKVarName($fk);
		
		$script .= "
	/**
	 * @var $className
	 */
	protected $".$varName.";
";
	}
	
	/**
	 * Adds the attributes used for foreign keys that reference this table.
	 * <code>protected collVarName;</code>
	 * <code>private lastVarNameCriteria = null;</code>
	 */
	protected function addRefFKAttributes(&$script, ForeignKey $fk)
	{
		$collName = $this->getRefFKCollVarName($fk);
		$lastCriteriaName = $this->getRefFKLastCriteriaVarName($fk);
		
		$script .= "
	/**
	 * Collection to store aggregation of $collName.
	 * @var array
	 */
	protected $".$collName.";
	
	/**
	 * The criteria used to select the current contents of $collName.
	 * @var Criteria
	 */
	private \$".$lastCriteriaName." = null;
";
	}
	
	protected function addFKMutator(&$script, ForeignKey $fk)
	{
		$className = $this->getFKClassname($fk);
		$methodAffix = $this->getPhpNameAffix($fk)
		$varName = $this->getFKVarName($fk);
		
		$script .= "
	/**
	 * Declares an association between this object and a $className object.
	 *
	 * @param $className \$v
	 * @return void
	 * @throws PropelException
	 */
	public function set$methodAffix(\$v)
	{
";
			foreach ($fk->getLocalColumns() as $columnName) {
				$column = $table->getColumn($columnName);
				$lfmap = $fk->getLocalForeignMapping();
				$colFKName = $lfmap[$columnName];
				$colFK = $tblFK->getColumn($colFKName);
				$script .= "

		if (\$v === null) {
			\$this->set".$column->getPhpName()."(".var_export($column->getDefaultValue(), true).");
		} else {
			\$this->set".$column->getPhpName()."(\$v->get".$colFK->getPhpName()."());
		}
";
	
			} /* foreach local col */

			$script .= "

		\$this->$varName = \$v;
	}
";
	}
	
	
	protected function addFKAccessor(&$script, ForeignKey $fk)
	{
	
		$className = $this->getFKClassname($fk);
		$methodAffix = $this->getPhpNameAffix($fk)
		$varName = $this->getFKVarName($fk);
		
		$and = "";
		$comma = "";
		$conditional = "";
		$arglist = "";
		$argsize = 0;
		foreach ($fk->getLocalColumns() as $columnName) {
			$column = $table->getColumn($columnName);
			$cptype = $column->getPhpNative();
			$clo = strtolower($column->getName());
			
			// FIXME: is this correct? what about negative numbers?
			if ($cptype == "integer" || $cptype == "float" || $cptype == "double") {
				$conditional .= $and . "\$this->". $clo ." > 0";
			} elseif($cptype == "string") {
				$conditional .= $and . "(\$this->" . $clo ." !== \"\" && \$this->".$clo." !== null)";
			} else {
				$conditional .= $and . "\$this->" . $clo ." !== null";
			}
			$arglist .= $comma . "\$this->" . $clo;
			$and = " && ";
			$comma = ", ";
			$argsize = $argsize + 1;
		}

		$pCollName = $table->getPhpName() . 's' . $relCol;
		
		// FIXME, if we are allowng user to specify class, this should be dynamic
		$fkPeerBuilder = new PHP5ComplexPeerBuilder($this->getFKTable($fk));
		
		$script .= "

	/**
	 * Get the associated $className object
	 *
	 * @param Connection Optional Connection object.
	 * @return $className The associated $className object.
	 * @throws PropelException
	 */
	public function get$pVarName(\$con = null)
	{
		// include the related Peer class
		include_once '".$this->getFilePath($fkPeerBuilder->getPackage(), $fkPeerBuilder->getPeerClassname())."';

		if (\$this->$varName === null && ($conditional)) {
";		
		$script .= "
			\$this->$varName = ".$fkPeerBuilder->getPeerClassname()."::".$fkPeerBuilder->getRetrieveMethodName()."(<?php echo $arglist ?>, \$con);
					
			/* The following can be used instead of the line above to
			   guarantee the related object contains a reference
			   to this object, but this level of coupling
			   may be undesirable in many circumstances.
			   As it can lead to a db query with many results that may
			   never be used.
			   \$obj = ".$fkPeerBuilder->getPeerClassname()."::retrieveByPK($arglist, \$con);
			   \$obj->add$pCollName(\$this);
			 */
		}
		return \$this->$varName;
	}
";

	} // addFKAccessor

	protected function addFkeyByKeyMutator(&$script, ForeignKey $fk)
	{
		$table = $this->getTable();
		
		#$className = $this->getFKClassname($fk);
		$methodAffix = $this->getPhpNameAffix($fk)
		#$varName = $this->getFKVarName($fk);
		
		$script .= "
	/**
	 * Provides convenient way to set a relationship based on a
	 * key.  e.g.
	 * <code>\$bar->setFooKey(\$foo->getPrimaryKey())</code>
	 *";
		if (count($fk->getLocalColumns()) > 1) {
			$script .= "
	 * Note: It is important that the xml schema used to create this class
	 * maintains consistency in the order of related columns between
	 * ".$table->getName()." and ". $tblFK->getName().".
	 * If for some reason this is impossible, this method should be
	 * overridden in <code>".$table->getPhpName()."</code>.";
		}
		$script .= "
	 * @return void
	 * @throws PropelException
	 */
	public function set".$pVarName."Key(\$key)
	{
";
		if (count($fk->getLocalColumns()) > 1) {
			$i = 0;
			foreach ($fk->getLocalColumns() as $colName) {
				$col = $table->getColumn($colName);
				$fktype = $col->getPhpNative();
				$script .= "
			\$this->set".$col->getPhpName()."( ($fktype) \$key[$i] );
";
				$i++;
			} /* foreach */
		} else {
			$lcols = $fk->getLocalColumns();
			$colName = $lcols[0];
			$col = $table->getColumn($colName);
			$fktype = $col->getPhpNative();
			$script .= "
		\$this->set".$col->getPhpName()."( ($fktype) \$key);
";
		}
		$script .= "
	}
";
	} // addFKByKeyMutator()
	
	
	protected function addRefererMethods(&$script)
	{
		
	}
	
	/**
	 * 
	 */
	protected function addRefererInit(&$script, ForeignKey $fk) {
	
		$relCol = $this->getPhpNameAffix($fk, $plural = true);
		$collName = $this->getRefFKCollVarName($fk);
		
		$script .= "
	/**
	 * Temporary storage of $collName to save a possible db hit in
	 * the event objects are add to the collection, but the
	 * complete collection is never requested.
	 * @return void
	 */
	public function init$relCol()
	{
		if (\$this->$collName === null) {
			\$this->$collName = array();
		}
	}
";
	} // addRefererInit()
	
	protected function addRefererAdd(&$script, ForeignKey $fk)
	{
		$tblFK = $this->getFKTable($fk);
		$relCol = $this->getPhpNameAffix($fk, $plural = true);
		$relColMs = $this->getPhpNameAffix($fk, $plural = false);
		$suffix = $this->getRelatedBySuffix($fk);
		
		$script .= "
	/**
	 * Method called to associate a ".$tblFK->getPhpName()." object to this object
	 * through the $className foreign key attribute
	 *
	 * @param $className \$l $className
	 * @return void
	 * @throws PropelException
	 */
	public function add$relColMs($className \$l)
	{
		\$this->$collName[] = \$l;
		\$l->set".$this->getTable()->getPhpName()."$suffix(\$this);
	}
";
	} // addRefererAdd
	
	protected function addRefererCount(&$script, ForeignKey $fk)
	{
		$relCol = $this->getPhpNameAffix($fk, $plural = true);
		
		$fkPeerBuilder = new PHP5ComplexPeerBuilder($this->getFKTable($fk));
		
		$script .= "
	/**
	 * Returns the number of related $relCol.
	 *
	 * @param Criteria \$criteria
	 * @param Connection \$con
	 * @throws PropelException
	 */
	public function count$relCol(\$criteria = null, \$con = null)
	{
		// include the Peer class
		include_once '".$this->getFilePath($fkPeerBuilder->getPackage(), $fkPeerBuilder->getPeerClassname())."';
		if (\$criteria === null) {
			\$criteria = new Criteria();
		}
";
		foreach ($fk->getForeignColumns() as $columnName) {
			$column = $table->getColumn($columnName);
			// used to be getLocalForeignMapping() but that didn't seem to work
			// (maybe a problem in translation of HashTable code to PHP).
			$flmap = $fk->getForeignLocalMapping();
			$colFKName = $flmap[$columnName];
			$colFK = $tblFK->getColumn($colFKName);
			$script .= "
		\$criteria->add(".$this->getColumnConstant($colFK, $className).", \$this->get".$column->getPhpName()."());
";
		} // end foreach ($fk->getForeignColumns()
		$script .="
		return ".$fkPeerBuilder->getPeerClassname()."::doCount(\$criteria, \$con);
	}
";
	} // addRefererCount
	
	protected function addRefererGet(&$script, ForeignKey $fk) 
	{
		$table = $this->getTable();
		$fkPeerBuilder = new PHP5ComplexPeerBuilder($this->getFKTable($fk));
		$relCol = $this->getPhpNameAffix($fk, $plural = true);
		
		$collName = $this->getRefFKCollVarName($fk);
		$lastCriteriaName = $this->getRefFKLastCriteriaVarName($fk);
		
		$script .= "
	/**
	 * If this collection has already been initialized with
	 * an identical criteria, it returns the collection.
	 * Otherwise if this ".$table->getPhpName()." has previously
	 * been saved, it will retrieve related $relCol from storage.
	 * If this ".$table->getPhpName()." is new, it will return
	 * an empty collection or the current collection, the criteria
	 * is ignored on a new object.
	 *
	 * @param Connection \$con
	 * @param Criteria \$criteria
	 * @throws PropelException
	 */
	public function get$relCol(\$criteria = null, \$con = null)
	{
		// include the Peer class
		include_once '".$this->getFilePath($fkPeerBuilder->getPackage(), $fkPeerBuilder->getPeerClassname())."';
		if (\$criteria === null) {
			\$criteria = new Criteria();
		}

		if (\$this->$collName === null) {
			if (\$this->isNew()) {
			   \$this->$collName = array();
			} else {
";	
		foreach ($fk->getForeignColumns() as $columnName) {
			$column = $table->getColumn($columnName);
			// used to be getLocalForeignMapping() but that didn't seem to work
			// (maybe a problem in translation of HashTable code to PHP).
			$flmap = $fk->getForeignLocalMapping();
			$colFKName = $flmap[$columnName];
			$colFK = $tblFK->getColumn($colFKName);
			$script .= "
				\$criteria->add(".$fkPeerBuilder->getColumnConstant($colFK).", \$this->get".$column->getPhpName()."());
";
		} // end foreach ($fk->getForeignColumns()
		$script .= "
				\$this->$collName = ".$fkPeerBuilder->getPeerClassname()."::doSelect(\$criteria, \$con);
			}
		} else {
			// criteria has no effect for a new object
			if (!\$this->isNew()) {
				// the following code is to determine if a new query is
				// called for.  If the criteria is the same as the last
				// one, just return the collection.
";
		foreach ($fk->getForeignColumns() as $columnName) {
			$column = $table->getColumn($columnName);
			$flmap = $fk->getForeignLocalMapping();
			$colFKName = $flmap[$columnName];
			$colFK = $tblFK->getColumn($colFKName);
			$script .= "

				\$criteria->add(".$fkPeerBuilder->getColumnConstant($colFK).", $this->get".$column->getPhpName()."());
";
	} // foreach ($fk->getForeignColumns()
$script .= "

				if (!isset(\$this->last".$relCol."Criteria) || !\$this->last".$relCol."Criteria->equals(\$criteria)) {
					\$this->$collName = ".$fkPeerBuilder->getPeerClassname()."::doSelect(\$criteria, \$con);
				}
			}
		}
		\$this->$lastCriteriaName = \$criteria;
		return \$this->$collName;
	}
";
	} // addRefererGet()
	
	
	
	protected function addFKAccessorJoinMethods(&$script, ForeignKey $fk)
	{
		$table = $this->getTable();
		$tableFK = $this->getFKTable($fk);
		
		$relCol = $this->getPhpNameAffix($fk, $plural=false);
		$lastCriteriaName = $this->getRefFKLastCriteriaVarName($fk);
		
		$fkPeerBuilder = new PHP5ComplexPeerBuilder($this->getFKTable($fk));
		
		$lastTable = "";
		foreach ($tblFK->getForeignKeys() as $fk2) {
		
			// Add join methods if the fk2 table is not this table or
			// the fk2 table references this table multiple times.

			$doJoinGet = true;
			
			if ( $fk2->getForeignTableName() == $table->getName() ) {
				$doJoinGet = false;
			}

			foreach ($fk2->getLocalColumns() as $columnName) {
				$column = $tblFK->getColumn($columnName);
				if ($column->isMultipleFK()) {
					$doJoinGet = true;
				}
			}

			$tblFK2 = $this->getFKTable($fk2);
			$doJoinGet = !$tblFK2->isForReferenceOnly();

			$fkClassName = $tblFK2->getPhpName();

			// do not generate code for self-referencing fk's, it would be
			// good to do, but it is just not implemented yet.
			if ($className == $fkClassName) {
				// $doJoinGet = false;  -- SELF REFERENCING FKs UNDER TESTING
			}
			
			$relCol2 = $this->getPhpNameAffix($fk2, $plural = false);

			if ( $this->getRelatedBySuffix($fk) != "" && 
							($this->getRelatedBySuffix($fk) == $this->getRelatedBySuffix($fk2))) {
				$doJoinGet = false;
			}

			if ($doJoinGet) {
				$script .= "

	/**
	 * If this collection has already been initialized with
	 * an identical criteria, it returns the collection.
	 * Otherwise if this ".$table->getPhpName()." is new, it will return
	 * an empty collection; or if this ".$table->getPhpName()." has previously
	 * been saved, it will retrieve related $relCol from storage.
	 *
	 * This method is protected by default in order to keep the public
	 * api reasonable.  You can provide public methods for those you
	 * actually need in ".$table->getPhpName().".
	 */
	public function get".$relCol."Join".$relCol2."(\$criteria = null, \$con = null)
	{
		// include the Peer class
		include_once '".$this->getFilePath($fkPeerBuilder->getPackage(), $fkPeerBuilder->getPeerClassname())."';
		if (\$criteria === null) {
			\$criteria = new Criteria();
		}

		if (\$this->$collName === null) {
			if (\$this->isNew()) {
				\$this->$collName = array();
			} else {
";
				foreach ($fk->getForeignColumns() as $columnName) {
					$column = $table->getColumn($columnName);
					$flMap = $fk->getForeignLocalMapping();
					$colFKName = $flMap[$columnName];
					$colFK = $tblFK->getColumn($colFKName);
					$script .= "
				\$criteria->add(".$fkPeerBuilder->getColumnConstant($colFK).", \$this->get".$column->getPhpName()."());
";
				} // end foreach ($fk->getForeignColumns()
		
				$script .= "
				\$this->$collName = ".$fkPeerBuilder->getPeerClassname()."::doSelectJoin$relCol2(\$criteria, \$con);
			}
		} else {
			// the following code is to determine if a new query is
			// called for.  If the criteria is the same as the last
			// one, just return the collection.
";
				foreach ($fk->getForeignColumns() as $columnName) {
					$column = $table->getColumn($columnName);
					$flMap = $fk->getForeignLocalMapping();
					$colFKName = $flMap[$columnName];
					$colFK = $tblFK->getColumn($colFKName);
					$script .= "
			\$criteria->add(".$fkPeerBuilder->getColumnConstant($colFK).", \$this->get".$column->getPhpName()."());
";
				} /* end foreach ($fk->getForeignColumns() */
			
				$script .= "
			if (!isset(\$this->$lastCriteriaName) || !\$this->$lastCriteriaName->equals(\$criteria)) {
				\$this->$collName = ".$fkPeerBuilder->getPeerClassname()."::doSelectJoin$relCol2(\$criteria, \$con);
			}
		}
		\$this->$lastCriteriaName = \$criteria;

		return \$this->$collName;
	}
";
			} /* end if($doJoinGet) */

		} /* end foreach ($tblFK->getForeignKeys() as $fk2) { */
	
	} // function
	
	
	/**
	 * Adds the doSave() method.
	 */
	protected function addDoSave(&$script)
	{
		$script .= "
	/**
	 * Stores the object in the database.
	 * 
	 * If the object is new, it inserts it; otherwise an update is performed.
	 * All related objects are also updated in this method.
	 *
	 * @param Connection \$con
	 * @return void
	 * @throws PropelException
	 * @see save()
	 */
	protected function doSave(\$con)
	{
		if (!\$this->alreadyInSave) {
			\$this->alreadyInSave = true;
";
	
		if (count($table->getForeignKeys())) {
			
			$script .= "

			// We call the save method on the following object(s) if they
			// were passed to this object by their coresponding set
			// method.  This object relates to these object(s) by a
			// foreign key reference.
";
		
		foreach($table->getForeignKeys() as $fk)
		{
			$aVarName = $this->getFKVarName($fk);
			$script .= "
			if (\$this->$aVarName !== null) {
				if (\$this->$aVarName->isModified()) \$this->$aVarName->save(\$con);
				$this->set".$this->getPhpNameAffix($fk, $plural = false)."(\$this->$aVarName);
			}
";
		}
	
		$script .= "	

			// If this object has been modified, then save it to the database.
			if ($this->isModified()) {
				if ($this->isNew()) {
					$pk = ".$this->getPeerClassname()."::doInsert(\$this, \$con);
";
		if ($table->getIdMethod() != "none") {
	
			if (count($pks = $table->getPrimaryKey())) {
				foreach ($pks as $pk) {
					if ($pk->isAutoIncrement()) {
						$script .= "
					\$this->set".$pk->getPhpName()."(\$pk);  //[IMV] update autoincrement primary key
";
					}
				}
			}
		}
	
		$script .= "
					\$this->setNew(false);
				} else {
					".$this->getPeerClassname()."::doUpdate(\$this, \$con);
				}
				\$this->resetModified(); // [HL] After being saved an object is no longer 'modified'
			}
";

		foreach ($table->getReferrers() as $fk) {
			$collName = $this->getRefFKCollVarName($fk);
			$scrpt .= "
			if (\$this->$collName !== null) {
				foreach(\$this->$collName as \$referrerFK) {
					\$referrerFK->save(\$con);
				}
			}
";
			} /* if tableFK !+ table */
		} /* foreach getReferrers() */
		$script .= "
			\$this->alreadyInSave = false;
		}
		
	} // doSave()
";
	
	}
	
	protected function addAlreadyInSaveAttribute(&$script)
	{
		$script .= "
	/**
	 * Flag to prevent endless save loop, if this object is referenced
	 * by another object which falls in this transaction.
	 * @var boolean
	 */
	private \$alreadyInSave = false;
";
	}
	
	
	
	protected function addSave(&$script)
	{
		$script .= "
	/**
	 * Stores the object in the database.  If the object is new,
	 * it inserts it; otherwise an update is performed.  This method
	 * wraps the doSave() worker method in a transaction.
	 *
	 * @param Connection \$con
	 * @return void
	 * @throws PropelException
	 * @see doSave()
	 */
	public function save(\$con = null)
	{
		if (\$this->isDeleted()) {
			throw new PropelException(\"You cannot save an object that has been deleted.\");
		}

		if (\$con === null) {
			\$con = Propel::getConnection(".$this->getPeerClassname()."::DATABASE_NAME);
		}
		
		try {
			\$con->begin();
			\$this->doSave(\$con);
			\$con->commit();
		} catch (PropelException \$e) {
			\$con->rollback();
			throw \$e;
		}
	}
";
	
	}
	
	protected function addAlreadyInValidationAttribute(&$script)
	{
		$script .= "
	/**
	 * Flag to prevent endless validation loop, if this object is referenced
	 * by another object which falls in this transaction.
	 * @var boolean
	 */
	private \$alreadyInValidation = false;
";
	}
	
	/**
	 * Adds the validate() method.
	 */
	protected function addValidate(&$script)
	{
		$script .= "
	/**
	 * Validates the objects modified field values and all objects related to this table.
	 *
	 * If \$columns is either a column name or an array of column names
	 * only those columns are validated.
	 *
	 * @param mixed \$columns Column name or an array of column names.
	 *
	 * @return mixed <code>true</code> if all columns pass validation
	 *			  or an array of <code>ValidationFailed</code> objects for columns that fail.
	 * @see doValidate()
	 */
	public function validate(\$columns = null)
	{
	  if (\$columns) {
		return ".$this->getPeerClassname()."::doValidate(\$this, \$columns);
	  }
		return \$this->doValidate();
	}
";
	} // addValidate()
	
	
	protected function addDoValidate(&$script)
	{
		$script .= "
	/**
	 * This function performs the validation work for complex object models.
	 *
	 * In addition to checking the current object, all related objects will
	 * also be validated.  If all pass then <code>true</code> is returned; otherwise
	 * an aggreagated array of ValidationFailed objects will be returned.
	 *
	 * @return mixed <code>true</code> if all validations pass; array of <code>ValidationFailed</code> objets otherwise.
	 */
	protected function doValidate()
	{
		if (!\$this->alreadyInValidation) {
			\$this->alreadyInValidation = true;
			\$retval = null;

			\$failureMap = array();
";
		if (count($table->getForeignKeys()) != 0) {
			$script .= "

			// We call the validate method on the following object(s) if they
			// were passed to this object by their coresponding set
			// method.  This object relates to these object(s) by a
			// foreign key reference.
";
			foreach($table->getForeignKeys() as $fk) {
				$aVarName = $this->getFKVarName($fk);
				$script .= "
			if (\$this->$aVarName !== null) {
				if ((\$retval = \$this->$aVarName->validate()) !== true) {
					\$failureMap = array_merge(\$failureMap, \$retval);
				}
			}
";
			} /* for() */
		} /* if count(fkeys) */
		
		$script .= "

			if ((\$retval = ".$this->getPeerClassname()."::doValidate(\$this)) !== true) {
				\$failureMap = array_merge(\$failureMap, \$retval);
			}

";

		foreach ($table->getReferrers() as $fk) {
			$collName = $this->getRefFKCollVarName($fk);
			$script .= "
			if (\$this->$collName !== null) {
				foreach(\$this->$collName as \$referrerFK) {
					if ((\$retval = \$referrerFK->validate()) !== true) {
						\$failureMap = array_merge(\$failureMap, \$retval);
					}
				}
			}
";
			} /* if tableFK !+ table */
		} /* foreach getReferrers() */
		
		$script .= "

			\$this->alreadyInValidation = false;
		}

		return (!empty(\$failureMap) ? \$failureMap : true);
	}
";
	} // addDoValidate()
	
	
	// Next (and last?): add copy() method.
	
} // PHP5BasicPeerBuilder
