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
	

	
} // PHP5BasicPeerBuilder
