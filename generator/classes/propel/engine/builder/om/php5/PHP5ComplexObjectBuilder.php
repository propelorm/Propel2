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
if ($complexObjectModel) {
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
	<?php } /* if complex object model */ ?>
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
	protected function getFKClassName(ForeignKey $fk)
	{
		$tblFK = $this->getTable()->getDatabase()->getTable($fk->getForeignTableName());
		return $tblFK->getPhpName();
	}
	
	/**
	 * Gets the PHP method name affix to be used (e.g. set????(), get????()).
	 * @return string
	 */
	protected function getFKMethodAffix(ForeignKey $fk)
	{
		$relCol = "";
		foreach ($fk->getLocalColumns() as $columnName) {
			$column = $table->getColumn($columnName);
			if ($column->isMultipleFK() || $fk->getForeignTableName() == $table->getName()) {
				$relCol .= $column->getPhpName();
			}
		}

		if ($relCol != "") {
			$relCol = "RelatedBy" . $relCol;
		}

		return $className . $relCol;
	}
	
	protected function getFKObjectVarName(ForeignKey $fk)
	{
		return 'a'.$this->getFkeyMethodAffix($fk);
	}
	
	protected function addFKMethods(&$script)
	{
	
		foreach ($table->getForeignKeys() as $fk) {
					
			//$this->addFkeyAttributes($script, $fk);
			$this->addFKMutator($script, $fk);
			$this->addFKAccessor($script, $fk);
						
		} // foreach fk
	
	}
	
	protected function addFKMutator(&$script, ForeignKey $fk)
	{
		$className = $this->getFKClassName($fk);
		$methodAffix = $this->getFKMethodAffix($fk)
		$varName = $this->getFKVarName($fk);
		
			$script .= "
	/**
	 * @var $className
	 */
	protected $".$varName.";

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
	
		$className = $this->getFKClassName($fk);
		$methodAffix = $this->getFKMethodAffix($fk)
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
		include_once '".$this->getFilePath($tblFKPackagePath, $fkPeerBuilder->getPeerClassname())."';

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

	
} // PHP5BasicPeerBuilder
