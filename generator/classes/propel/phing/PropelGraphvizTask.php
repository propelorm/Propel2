<?php

/*
 *  $Id: PropelGraphvizTask.php,v 1.1 2005/01/25 23:56:43 hlellelid Exp $
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

include_once 'propel/engine/database/model/AppData.php';
//require_once 'phing/Task.php';

/**
 * A task to generate Graphviz png images from propel datamodel.
 *
 * @author Mark Kimsal
 * @version $Revision: 1.1 $
 * @package propel.phing
 */
class PropelGraphvizTask extends AbstractPropelDataModelTask {

	/**
	 * The properties file that maps an SQL file to a particular database.
	 * @var File
	 */
	private $sqldbmap;
	
	/**
	 * Name of the database.
	 */
	private $database;

	/**
	 * Name of the output directory.
	 */
	private $outDir;
	

	/**
	 * Set the sqldbmap.
	 * @param File $sqldbmap The db map.
	 */
	public function setOutputDirectory(File $out)
	{
		$this->outDir = $out;
	}


	/**
	 * Set the sqldbmap.
	 * @param File $sqldbmap The db map.
	 */
	public function setSqlDbMap(File $sqldbmap)
	{
		$this->sqldbmap = $sqldbmap;
	}

	/**
	 * Get the sqldbmap.
	 * @return File $sqldbmap.
	 */
	public function getSqlDbMap()
	{
		return $this->sqldbmap;
	}
	
	/**
	 * Set the database name.
	 * @param string $database
	 */
	public function setDatabase($database)
	{
		$this->database = $database;
	}

	/**
	 * Get the database name.
	 * @return string
	 */
	public function getDatabase()
	{
		return $this->database;
	}


	public function main()
	{

		$count = 0;

		$dotSyntax = '';

		// file we are going to create

		foreach ($this->getDataModels() as $dataModel) {

			echo "Processing datamodel...\n";

			$dotSyntax .= "digraph G {\n";
			foreach ($dataModel->getDatabases() as $database) {
				echo "Processing database..." . $database->getName() . "\n";

				//print the tables
				foreach($database->getTables() as $tbl) {		
					echo "Processing table..." . $tbl->getName() . "\n";
					++$count;
					$dotSyntax .= 'node'.$tbl->getName().' [label="{<table>'.$tbl->getName().'|<cols>';

					foreach ($tbl->getColumns() as $col) {
						$dotSyntax .= $col->getName();
						if ($col->getForeignKey() != null ) {
							$dotSyntax .= ' (FK)';
						} elseif ($col->isPrimaryKey()) {
							$dotSyntax .= ' (PK)';
						}
						$dotSyntax .= '\l';
					}
					$dotSyntax .= '}", shape=record];';
					$dotSyntax .= "\n";

				}

				//print the relations

				$count = 0;
				$dotSyntax .= "\n";
				foreach($database->getTables() as $tbl) {		
					++$count;

					foreach ($tbl->getColumns() as $col) {
						$fk = $col->getForeignKey();
						if ( $fk == null ) continue;
						$dotSyntax .= 'node'.$tbl->getName() .':cols -> node'.$fk->getForeignTableName() . ':table [label="' . $col->getName() . '=' . $fk->getForeignColumnNames() . ' "];';
						$dotSyntax .= "\n";
					}
				}



			} // foreach database		
			$dotSyntax .= "}\n";

			$this->writePNG($dotSyntax,$this->outDir->toString(),"schema.png");
		} //foreach datamodels			
		
	} // main()


	/**
	 * probably insecure 
	 */
	function writePNG($dotSyntax, $outputDir, $filename) {
		print "Writing file...\n$dotSyntax\n\n";
		$dot = fopen($outputDir.'/schema.dot','w');
		fputs($dot,$dotSyntax);
		fclose($dot);
		exec('dot '.$outputDir.'/schema.dot -Tpng -o '.$outputDir.'/schema.png');
	}

}
