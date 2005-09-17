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
	 * @param string &$script The script will be modified in this method.
	 * @see addFKAttributes()
	 * @see addRefFKAttributes()
	 * @see addAlreadyInSaveAttribute()
	 * @see addAlreadyInValidationAttribute()
	 */
	protected function addAttributes(&$script)
	{
		$table = $this->getTable();
		parent::addAttributes($script);
		
		foreach ($table->getForeignKeys() as $fk) {
			$this->addFKAttributes($script, $fk);
		}
		
		foreach($table->getReferrers() as $refFK) {
			// if ($refFK->getTable()->getName() != $table->getName()) {
				$this->addRefFKAttributes($script, $refFK);
			// }
		}
		
		$this->addAlreadyInSaveAttribute($script);
		$this->addAlreadyInValidationAttribute($script);
	}
	
	/**
	 * Specifies the methods that are added as part of the basic OM class.
	 * This can be overridden by subclasses that wish to add more methods.
	 * @param string &$script The script will be modified in this method.
	 * @see PHP5BasicObjectBuilder::addClassBody()
	 */
	protected function addClassBody(&$script)
	{
		$table = $this->getTable();
		parent::addClassBody($script);
		
		
		$this->addFKMethods($script);
		$this->addRefFKMethods($script);
		
	}
	
	/**
	 * Adds the close of mutator (setter) method for a column.
	 * This method overrides the method from PHP5BasicObjectBuilder in order to
	 * account for updating related objects.
	 * @param string &$script The script will be modified in this method.
	 * @param Column $col The current column.
	 * @see PHP5BasicObjectBuilder::addMutatorClose()
	 */
	protected function addMutatorClose(&$script, Column $col)
	{
		$table = $this->getTable();
		$cfc=$col->getPhpName();
		$clo=strtolower($col->getName());
		
		if ($col->isForeignKey()) {
		
			$tblFK = $table->getDatabase()->getTable($col->getRelatedTableName());
			$colFK = $tblFK->getColumn($col->getRelatedColumnName());
			
			$varName = $this->getFKVarName($col->getForeignKey());

			$script .= "
		if (\$this->$varName !== null && \$this->".$varName."->get".$colFK->getPhpName()."() !== \$v) {
			\$this->$varName = null;
		}		
";
		} /* if col is foreign key */

		foreach ($col->getReferrers() as $fk) {
			
			$tblFK = $this->getDatabase()->getTable($fk->getForeignTableName());
			
			if ( $tblFK->getName() != $table->getName() ) {
			
				$collName = $this->getRefFKCollVarName($fk);
								
				$tblFK = $table->getDatabase()->getTable($col->getRelatedTableName());
				$colFK = $tblFK->getColumn($col->getRelatedColumnName());
			
				$script .= "

		// update associated ".$tblFK->getPhpName()."
		if (\$this->$collName !== null) {
			foreach(\$this->$collName as \$referrerObject) {
				  \$referrerObject->set".$colFK->getPhpName()."(\$v);
			  }
		  }
";
			} // if
		} // foreach

		$script .= "
	} // set$cfc()
";			
	} // addMutatorClose()
	
	/**
	 * Adds the methods related to validating, saving and deleting the object.
	 */
	protected function addManipulationMethods(&$script)
	{
		$this->addDelete($script);
		
		$this->addSave($script);
		$this->addDoSave($script);
		
		$this->addValidate($script);
		$this->addDoValidate($script);
	}
	
	/**
	 * Convenience method to get the foreign Table object for an fkey.
	 * @return Table
	 */
	protected function getForeignTable(ForeignKey $fk)
	{
		return $this->getTable()->getDatabase()->getTable($fk->getForeignTableName());
	}
	
	/**
	 * Gets the PHP method name affix to be used for fkeys for the current table (not referrers to this table).
	 * 
	 * The difference between this method and the getRefFKPhpNameAffix() method is that in this method the
	 * classname in the affix is the foreign table classname.
	 * 
	 * @param ForeignKey $fk The local FK that we need a name for.
	 * @param boolean $plural Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
	 * @return string
	 */
	public function getFKPhpNameAffix(ForeignKey $fk, $plural = false)
	{
		$className = $this->getForeignTable($fk)->getPhpName();				
		return $className . ($plural ? 's' : '') . $this->getRelatedBySuffix($fk);
	}
	
	/**
	 * Gets the PHP method name affix to be used for referencing foreign key methods and variable names (e.g. set????(), $coll???).
	 * 
	 * The difference between this method and the getFKPhpNameAffix() method is that in this method the
	 * classname in the affix is the classname of the local fkey table.
	 * 
	 * @param ForeignKey $fk The referrer FK that we need a name for.
	 * @param boolean $plural Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
	 * @return string
	 */
	public function getRefFKPhpNameAffix(ForeignKey $fk, $plural = false)
	{
		$className = $fk->getTable()->getPhpName();
		return $className . ($plural ? 's' : '') . $this->getRelatedBySuffix($fk);
	}
	
	/**
	 * Gets the "RelatedBy*" suffix (if needed) that is attached to method and variable names.
	 * 
	 * The related by suffix is based on the local columns of the foreign key.  If there is more than
	 * one column in a table that points to the same foreign table, then a 'RelatedByLocalColName' suffix 
	 * will be appended.
	 * 
	 * @return string
	 */
	protected function getRelatedBySuffix(ForeignKey $fk)
	{
		$relCol = "";
		foreach ($fk->getLocalColumns() as $columnName) {
			$column = $fk->getTable()->getColumn($columnName);
			if (!$column) {
			    $e = new Exception("Could not fetch column: $columnName in table " . $fk->getTable()->getName());
				print $e;
				throw $e;
			}
			if ($column->isMultipleFK() || $fk->getForeignTableName() == $fk->getTable()->getName()) {
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
	
	protected function getFKVarName(ForeignKey $fk)
	{
		return 'a' . $this->getFKPhpNameAffix($fk, $plural = false);
	}
	
	protected function getRefFKCollVarName(ForeignKey $fk)
	{
		return 'coll' . $this->getRefFKPhpNameAffix($fk, $plural = true);
	}
	
	protected function getRefFKLastCriteriaVarName(ForeignKey $fk)
	{
		return 'last' . $this->getRefFKPhpNameAffix($fk, $plural = false) . 'Criteria';
	}
	
	// ----------------------------------------------------------------
	//
	// F K    M E T H O D S
	//
	// ----------------------------------------------------------------	

	/**
	 * Adds the methods that get & set objects related by foreign key to the current object.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addFKMethods(&$script)
	{	
		foreach ($this->getTable()->getForeignKeys() as $fk) {
			$this->addFKMutator($script, $fk);
			$this->addFKAccessor($script, $fk);
		} // foreach fk
	}
	
	/**
	 * Adds the class attributes that are needed to store fkey related objects.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addFKAttributes(&$script, ForeignKey $fk)
	{
		$className = $this->getForeignTable($fk)->getPhpName();
		$varName = $this->getFKVarName($fk);
		
		$script .= "
	/**
	 * @var $className
	 */
	protected $".$varName.";
";
	}
	
	/**
	 * Adds the mutator (setter) method for setting an fkey related object.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addFKMutator(&$script, ForeignKey $fk)
	{
		$table = $this->getTable();
		$tblFK = $this->getForeignTable($fk);
		$className = $this->getForeignTable($fk)->getPhpName();
		$varName = $this->getFKVarName($fk);
		
		$script .= "
	/**
	 * Declares an association between this object and a $className object.
	 *
	 * @param $className \$v
	 * @return void
	 * @throws PropelException
	 */
	public function set".$this->getFKPhpNameAffix($fk, $plural = false)."(\$v)
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
	
	/**
	 * Adds the accessor (getter) method for getting an fkey related object.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addFKAccessor(&$script, ForeignKey $fk)
	{
		$table = $this->getTable();
		
		$className = $this->getForeignTable($fk)->getPhpName();
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

		$pCollName = $this->getFKPhpNameAffix($fk, $plural = true);
		
		$fkPeerBuilder = OMBuilder::getNewPeerBuilder($this->getForeignTable($fk));
		
		$script .= "

	/**
	 * Get the associated $className object
	 *
	 * @param Connection Optional Connection object.
	 * @return $className The associated $className object.
	 * @throws PropelException
	 */
	public function get".$this->getFKPhpNameAffix($fk, $plural = false)."(\$con = null)
	{
		// include the related Peer class
		include_once '".$fkPeerBuilder->getClassFilePath()."';

		if (\$this->$varName === null && ($conditional)) {
";		
		$script .= "
			\$this->$varName = ".$fkPeerBuilder->getPeerClassname()."::".$fkPeerBuilder->getRetrieveMethodName()."($arglist, \$con);
					
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
	
	/**
	 * Adds a convenience method for setting a related object by specifying the primary key.
	 * This can be used in conjunction with the getPrimaryKey() for systems where nothing is known
	 * about the actual objects being related.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addFKByKeyMutator(&$script, ForeignKey $fk)
	{
		$table = $this->getTable();
		
		#$className = $this->getForeignTable($fk)->getPhpName();
		$methodAffix = $this->getFKPhpNameAffix($fk);
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
	public function set".$methodAffix."Key(\$key)
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
	
	/**
	 * Adds the method that fetches fkey-related (referencing) objects but also joins in data from another table.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addRefFKGetJoinMethods(&$script, ForeignKey $refFK)
	{
		$table = $this->getTable();
		$tblFK = $refFK->getTable();
		
		$relCol = $this->getRefFKPhpNameAffix($refFK, $plural=true);
		$collName = $this->getRefFKCollVarName($refFK);
		$lastCriteriaName = $this->getRefFKLastCriteriaVarName($refFK);
		
		$fkPeerBuilder = OMBuilder::getNewPeerBuilder($tblFK);
		
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

			$tblFK2 = $this->getForeignTable($fk2);
			$doJoinGet = !$tblFK2->isForReferenceOnly();

			// it doesn't make sense to join in rows from the curent table, since we are fetching
			// objects related to *this* table (i.e. the joined rows will all be the same row as current object)
			if ($this->getTable()->getPhpName() == $tblFK2->getPhpName()) {
				$doJoinGet = false;
			}
			
			$relCol2 = $this->getFKPhpNameAffix($fk2, $plural = false);

			if ( $this->getRelatedBySuffix($refFK) != "" && 
							($this->getRelatedBySuffix($refFK) == $this->getRelatedBySuffix($fk2))) {
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
		include_once '".$fkPeerBuilder->getClassFilePath()."';
		if (\$criteria === null) {
			\$criteria = new Criteria();
		}
		elseif (\$criteria instanceof Criteria)
		{
			\$criteria = clone \$criteria;
		}

		if (\$this->$collName === null) {
			if (\$this->isNew()) {
				\$this->$collName = array();
			} else {
";
				foreach ($refFK->getForeignColumns() as $columnName) {
					$column = $table->getColumn($columnName);
					$flMap = $refFK->getForeignLocalMapping();
					$colFKName = $flMap[$columnName];
					$colFK = $tblFK->getColumn($colFKName);
					if ($colFK === null) {
					    $e = new Exception("Column $colFKName not found in " . $tblFK->getName());
						print $e;
						throw $e;
					}
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
				foreach ($refFK->getForeignColumns() as $columnName) {
					$column = $table->getColumn($columnName);
					$flMap = $refFK->getForeignLocalMapping();
					$colFKName = $flMap[$columnName];
					$colFK = $tblFK->getColumn($colFKName);
					$script .= "
			\$criteria->add(".$fkPeerBuilder->getColumnConstant($colFK).", \$this->get".$column->getPhpName()."());
";
				} /* end foreach ($fk->getForeignColumns() */
			
				$script .= "
			if (!isset(\$this->$lastCriteriaName) || !\$this->".$lastCriteriaName."->equals(\$criteria)) {
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
	
	
	// ----------------------------------------------------------------
	//
	// R E F E R R E R    F K    M E T H O D S
	//
	// ----------------------------------------------------------------	
	
	/**
	 * Adds the attributes used to store objects that have referrer fkey relationships to this object.
	 * <code>protected collVarName;</code>
	 * <code>private lastVarNameCriteria = null;</code>
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addRefFKAttributes(&$script, ForeignKey $refFK)
	{
		$collName = $this->getRefFKCollVarName($refFK);
		$lastCriteriaName = $this->getRefFKLastCriteriaVarName($refFK);
		
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
	
	/**
	 * Adds the methods for retrieving, initializing, adding objects that are related to this one by foreign keys.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addRefFKMethods(&$script)
	{
		foreach($this->getTable()->getReferrers() as $refFK) {
			// if ( $refFK->getTable()->getName() != $this->getTable()->getName() ) {
				$this->addRefFKInit($script, $refFK);
				$this->addRefFKGet($script, $refFK);
				$this->addRefFKCount($script, $refFK);
				$this->addRefFKAdd($script, $refFK);
				$this->addRefFKGetJoinMethods($script, $refFK);
			// }
		}
	}
	
	/**
	 * Adds the method that initializes the referrer fkey collection.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addRefFKInit(&$script, ForeignKey $refFK) {
	
		$relCol = $this->getRefFKPhpNameAffix($refFK, $plural = true);
		$collName = $this->getRefFKCollVarName($refFK);
		
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
	
	/**
	 * Adds the method that adds an object into the referrer fkey collection.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addRefFKAdd(&$script, ForeignKey $refFK)
	{
		$tblFK = $refFK->getTable();
		$className = $refFK->getTable()->getPhpName();
		
		$joinedTableObjectBuilder = OMBuilder::getNewObjectBuilder($refFK->getTable());
		
		$script .= "
	/**
	 * Method called to associate a ".$tblFK->getPhpName()." object to this object
	 * through the $className foreign key attribute
	 *
	 * @param $className \$l $className
	 * @return void
	 * @throws PropelException
	 */
	public function add".$this->getRefFKPhpNameAffix($refFK, $plural = false)."($className \$l)
	{
		\$this->coll".$this->getRefFKPhpNameAffix($refFK, $plural = true)."[] = \$l;
		\$l->set".$this->getFKPhpNameAffix($refFK, $plural = false)."(\$this);
	}
";
	} // addRefererAdd
	
	/**
	 * Adds the method that returns the size of the referrer fkey collection.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addRefFKCount(&$script, ForeignKey $refFK)
	{
		$relCol = $this->getRefFKPhpNameAffix($refFK, $plural = true);
		
		$fkPeerBuilder = OMBuilder::getNewPeerBuilder($refFK->getTable());
		
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
		include_once '".$fkPeerBuilder->getClassFilePath()."';
		if (\$criteria === null) {
			\$criteria = new Criteria();
		}
		elseif (\$criteria instanceof Criteria)
		{
			\$criteria = clone \$criteria;
		}
";
		foreach ($refFK->getForeignColumns() as $columnName) {
			$column = $this->getTable()->getColumn($columnName);
			$flmap = $refFK->getForeignLocalMapping();
			$colFKName = $flmap[$columnName];
			$colFK = $refFK->getTable()->getColumn($colFKName);
			$script .= "
		\$criteria->add(".$fkPeerBuilder->getColumnConstant($colFK).", \$this->get".$column->getPhpName()."());
";
		} // end foreach ($fk->getForeignColumns()
		$script .="
		return ".$fkPeerBuilder->getPeerClassname()."::doCount(\$criteria, \$con);
	}
";
	} // addRefererCount
	
	/**
	 * Adds the method that returns the referrer fkey collection.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addRefFKGet(&$script, ForeignKey $refFK) 
	{
		$table = $this->getTable();
		$tblFK = $refFK->getTable();
		
		$fkPeerBuilder = OMBuilder::getNewPeerBuilder($refFK->getTable());
		$relCol = $this->getRefFKPhpNameAffix($refFK, $plural = true);
		
		$collName = $this->getRefFKCollVarName($refFK);
		$lastCriteriaName = $this->getRefFKLastCriteriaVarName($refFK);
		
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
		include_once '".$fkPeerBuilder->getClassFilePath()."';
		if (\$criteria === null) {
			\$criteria = new Criteria();
		}
		elseif (\$criteria instanceof Criteria)
		{
			\$criteria = clone \$criteria;
		}

		if (\$this->$collName === null) {
			if (\$this->isNew()) {
			   \$this->$collName = array();
			} else {
";	
		foreach ($refFK->getLocalColumns() as $colFKName) {
			// $colFKName is local to the referring table (i.e. foreign to this table)
			$lfmap = $refFK->getLocalForeignMapping();
			$localColumn = $this->getTable()->getColumn($lfmap[$colFKName]);			
			$colFK = $refFK->getTable()->getColumn($colFKName);
			
			$script .= "
				\$criteria->add(".$fkPeerBuilder->getColumnConstant($colFK).", \$this->get".$localColumn->getPhpName()."());
";
		} // end foreach ($fk->getForeignColumns()
		
		$script .= "
				".$fkPeerBuilder->getPeerClassname()."::addSelectColumns(\$criteria);
				\$this->$collName = ".$fkPeerBuilder->getPeerClassname()."::doSelect(\$criteria, \$con);
			}
		} else {
			// criteria has no effect for a new object
			if (!\$this->isNew()) {
				// the following code is to determine if a new query is
				// called for.  If the criteria is the same as the last
				// one, just return the collection.
";
		foreach ($refFK->getLocalColumns() as $colFKName) {
			// $colFKName is local to the referring table (i.e. foreign to this table)
			$lfmap = $refFK->getLocalForeignMapping();
			$localColumn = $this->getTable()->getColumn($lfmap[$colFKName]);			
			$colFK = $refFK->getTable()->getColumn($colFKName);
			$script .= "

				\$criteria->add(".$fkPeerBuilder->getColumnConstant($colFK).", \$this->get".$localColumn->getPhpName()."());
";
	} // foreach ($fk->getForeignColumns()
$script .= "
				".$fkPeerBuilder->getPeerClassname()."::addSelectColumns(\$criteria);
				if (!isset(\$this->$lastCriteriaName) || !\$this->".$lastCriteriaName."->equals(\$criteria)) {
					\$this->$collName = ".$fkPeerBuilder->getPeerClassname()."::doSelect(\$criteria, \$con);
				}
			}
		}
		\$this->$lastCriteriaName = \$criteria;
		return \$this->$collName;
	}
";
	} // addRefererGet()
	
	
	
	// ----------------------------------------------------------------
	//
	// M A N I P U L A T I O N    M E T H O D S
	//
	// ----------------------------------------------------------------	
	
	/**
	 * Adds the workhourse doSave() method.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addDoSave(&$script)
	{
		$table = $this->getTable();
		
		$script .= "
	/**
	 * Stores the object in the database.
	 * 
	 * If the object is new, it inserts it; otherwise an update is performed.
	 * All related objects are also updated in this method.
	 *
	 * @param Connection \$con
	 * @return int The number of rows affected by this insert/update and any referring fk objects' save() operations.
	 * @throws PropelException
	 * @see save()
	 */
	protected function doSave(\$con)
	{
		\$affectedRows = 0; // initialize var to track total num of affected rows	
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
				if (\$this->".$aVarName."->isModified()) {
					\$affectedRows += \$this->".$aVarName."->save(\$con);
				}
				\$this->set".$this->getFKPhpNameAffix($fk, $plural = false)."(\$this->$aVarName);
			}
";
			} // foreach foreign k
		} // if (count(foreign keys))
		
		$script .= "	

			// If this object has been modified, then save it to the database.
			if (\$this->isModified()) {
				if (\$this->isNew()) {
					\$pk = ".$this->getPeerClassname()."::doInsert(\$this, \$con);
					\$affectedRows += 1; // we are assuming that there is only 1 row per doInsert() which 
										 // should always be true here (even though technically 
										 // BasePeer::doInsert() can insert multiple rows).
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
		} // if (id method != "none")
	
		$script .= "
					\$this->setNew(false);
				} else {
					\$affectedRows += ".$this->getPeerClassname()."::doUpdate(\$this, \$con);
				}
				\$this->resetModified(); // [HL] After being saved an object is no longer 'modified'
			}
";

		foreach ($table->getReferrers() as $fk) {
			$collName = $this->getRefFKCollVarName($fk);
			if ( $fk->getTable()->getName() != $table->getName() ) {
				$script .= "
			if (\$this->$collName !== null) {
				foreach(\$this->$collName as \$referrerFK) {
					if (!\$referrerFK->isDeleted()) {
						\$affectedRows += \$referrerFK->save(\$con);
					}
				}
			}
";
			} /* if tableFK != table */
		} /* foreach getReferrers() */
		$script .= "
			\$this->alreadyInSave = false;
		}
		return \$affectedRows;
	} // doSave()
";
	
	}
	
	/**
	 * Adds the $alreadyInSave attribute, which prevents attempting to re-save the same object.
	 * @param string &$script The script will be modified in this method.
	 */
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
	
	/**
	 * Adds the save() method.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addSave(&$script)
	{
		$script .= "
	/**
	 * Stores the object in the database.  If the object is new,
	 * it inserts it; otherwise an update is performed.  This method
	 * wraps the doSave() worker method in a transaction.
	 *
	 * @param Connection \$con
	 * @return int The number of rows affected by this insert/update and any referring fk objects' save() operations.
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
			\$affectedRows = \$this->doSave(\$con);
			\$con->commit();
			return \$affectedRows;
		} catch (PropelException \$e) {
			\$con->rollback();
			throw \$e;
		}
	}
";
	
	}
	
	/**
	 * Adds the $alreadyInValidation attribute, which prevents attempting to re-validate the same object.
	 * @param string &$script The script will be modified in this method.
	 */
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
	 * @param string &$script The script will be modified in this method.
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
	
	/**
	 * Adds the workhourse doValidate() method.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addDoValidate(&$script)
	{
		$table = $this->getTable();
		
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
				if ((\$retval = \$this->".$aVarName."->validate()) !== true) {
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
			$tblFK = $fk->getTable();
			if ( $tblFK->getName() != $table->getName() ) {
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

	/**
	 * Adds the copy() method, which (in complex OM) includes the $deepCopy param for making copies of related objects.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addCopy(&$script)
	{
		$this->addCopyInto($script);
		
		$table = $this->getTable();

		$script .= "
	/**
	 * Makes a copy of this object that will be inserted as a new row in table when saved.
	 * It creates a new object filling in the simple attributes, but skipping any primary
	 * keys that are defined for the table.
	 * 
	 * If desired, this method can also make copies of all associated (fkey referrers)
	 * objects.
	 *
	 * @param boolean \$deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
	 * @return ".$table->getPhpName()." Clone of current object.
	 * @throws PropelException
	 */
	public function copy(\$deepCopy = false)
	{
		// we use get_class(), because this might be a subclass
		\$clazz = get_class(\$this);
		\$copyObj = new \$clazz();
		\$this->copyInto(\$copyObj, \$deepCopy);
		return \$copyObj;
	}
";
	} // addCopy()
	
	/**
	 * Adds the copyInto() method, which takes an object and sets contents to match current object.
	 * In complex OM this method includes the $deepCopy param for making copies of related objects.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addCopyInto(&$script)
	{
		$table = $this->getTable();

		$script .= "
	/**
	 * Sets contents of passed object to values from current object.
	 * 
	 * If desired, this method can also make copies of all associated (fkey referrers)
	 * objects.
	 *
	 * @param object \$copyObj An object of ".$table->getPhpName()." (or compatible) type.
	 * @param boolean \$deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
	 * @throws PropelException
	 */
	public function copyInto(\$copyObj, \$deepCopy = false)
	{
";

		$pkcols = array();
		foreach ($table->getColumns() as $pkcol) {
			if ($pkcol->isPrimaryKey()) {
				$pkcols[] = $pkcol->getName();
			}
		}

		foreach ($table->getColumns() as $col) {
			if (!in_array($col->getName(), $pkcols)) {
				$script .= "
		\$copyObj->set".$col->getPhpName()."(\$this->".strtolower($col->getName()).");
";
			}
		} // foreach

		// Avoid useless code by checking to see if there are any referrers
		// to this table:
		if (count($table->getReferrers()) > 0) {
			$script .= "

		if (\$deepCopy) {
			// important: temporarily setNew(false) because this affects the behavior of
			// the getter/setter methods for fkey referrer objects.
			\$copyObj->setNew(false);
";
			foreach ($table->getReferrers() as $fk) {
				if ( $fk->getTable()->getName() != $table->getName() ) {
					$script .= "
			foreach(\$this->get".$this->getRefFKPhpNameAffix($fk, true)."() as \$relObj) {
				\$copyObj->add".$this->getRefFKPhpNameAffix($fk)."(\$relObj->copy());
			}
";
				} /* if tblFK != table */
			} /* foreach */
			$script .= "
		} // if (\$deepCopy)
";
		} /* if (count referrers > 0 ) */
			
		$script .= "

		\$copyObj->setNew(true);
";


		foreach ($table->getColumns() as $col) {
			if ($col->isPrimaryKey()) {
				$coldefval = $col->getPhpDefaultValue();
				$coldefval = var_export($coldefval, true);
				$script .= "
		\$copyObj->set".$col->getPhpName() ."($coldefval); // this is a pkey column, so set to default value
";
			} // if col->isPrimaryKey
		} // foreach
		$script .= "
	}
";
	} // addCopyInto()
	
} // PHP5ComplexObjectBuilder
