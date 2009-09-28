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

require_once 'propel/phing/AbstractPropelDataModelTask.php';
include_once 'propel/engine/builder/om/ClassTools.php';
require_once 'propel/engine/builder/om/OMBuilder.php';

/**
 * This Task creates the OM classes based on the XML schema file.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.phing
 */
class PropelOMTask extends AbstractPropelDataModelTask {

	/**
	 * The platform (php4, php5, etc.) for which the om is being built.
	 * @var        string
	 */
	private $targetPlatform;

	/**
	 * Sets the platform (php4, php5, etc.) for which the om is being built.
	 * @param      string $v
	 */
	public function setTargetPlatform($v) {
		$this->targetPlatform = $v;
	}

	/**
	 * Gets the platform (php4, php5, etc.) for which the om is being built.
	 * @return     string
	 */
	public function getTargetPlatform() {
		return $this->targetPlatform;
	}

	/**
	 * Utility method to create directory for package if it doesn't already exist.
	 * @param      string $path The [relative] package path.
	 * @throws     BuildException - if there is an error creating directories
	 */
	protected function ensureDirExists($path)
	{
		$f = new PhingFile($this->getOutputDirectory(), $path);
		if (!$f->exists()) {
			if (!$f->mkdirs()) {
				throw new BuildException("Error creating directories: ". $f->getPath());
			}
		}
	}

	/**
	 * Uses a builder class to create the output class.
	 * This method assumes that the DataModelBuilder class has been initialized with the build properties.
	 * @param      OMBuilder $builder
	 * @param      boolean $overwrite Whether to overwrite existing files with te new ones (default is YES).
	 * @todo       -cPropelOMTask Consider refactoring build() method into AbstractPropelDataModelTask (would need to be more generic).
	 */
	protected function build(OMBuilder $builder, $overwrite = true)
	{

		$path = $builder->getClassFilePath();
		$this->ensureDirExists(dirname($path));

		$_f = new PhingFile($this->getOutputDirectory(), $path);
		if ($overwrite || !$_f->exists()) {
			$this->log("\t\t-> " . $builder->getClassname() . " [builder: " . get_class($builder) . "]");
			$script = $builder->build();
			file_put_contents($_f->getAbsolutePath(), $script);
			foreach ($builder->getWarnings() as $warning) {
				$this->log($warning, Project::MSG_WARN);
			}
		} else {
			$this->log("\t\t-> (exists) " . $builder->getClassname());
		}

	}

	/**
	 * Main method builds all the targets for a typical propel project.
	 */
	public function main()
	{
		// check to make sure task received all correct params
		$this->validate();

		$generatorConfig = $this->getGeneratorConfig();

		foreach ($this->getDataModels() as $dataModel) {
			$this->log("Processing Datamodel : " . $dataModel->getName());

			foreach ($dataModel->getDatabases() as $database) {

				$this->log("  - processing database : " . $database->getName());

				foreach ($database->getTables() as $table) {

					if (!$table->isForReferenceOnly()) {

						$this->log("\t+ " . $table->getName());

						// -----------------------------------------------------------------------------------------
						// Create Peer, Object, and TableMap classes
						// -----------------------------------------------------------------------------------------

						// these files are always created / overwrite any existing files
						foreach (array('peer', 'object', 'tablemap') as $target) {
							$builder = $generatorConfig->getConfiguredBuilder($table, $target);
							$this->build($builder);
						}

						// -----------------------------------------------------------------------------------------
						// Create [empty] stub Peer and Object classes if they don't exist
						// -----------------------------------------------------------------------------------------

						// these classes are only generated if they don't already exist
						foreach (array('peerstub', 'objectstub') as $target) {
							$builder = $generatorConfig->getConfiguredBuilder($table, $target);
							$this->build($builder, $overwrite=false);
						}

						// -----------------------------------------------------------------------------------------
						// Create [empty] stub child Object classes if they don't exist
						// -----------------------------------------------------------------------------------------

						// If table has enumerated children (uses inheritance) then create the empty child stub classes if they don't already exist.
						if ($table->getChildrenColumn()) {
							$col = $table->getChildrenColumn();
							if ($col->isEnumeratedClasses()) {
								foreach ($col->getChildren() as $child) {
									$builder = $generatorConfig->getConfiguredBuilder($table, 'objectmultiextend');
									$builder->setChild($child);
									$this->build($builder, $overwrite=false);
								} // foreach
							} // if col->is enumerated
						} // if tbl->getChildrenCol


						// -----------------------------------------------------------------------------------------
						// Create [empty] Interface if it doesn't exist
						// -----------------------------------------------------------------------------------------

						// Create [empty] interface if it does not already exist
						if ($table->getInterface()) {
							$builder = $generatorConfig->getConfiguredBuilder($table, 'interface');
							$this->build($builder, $overwrite=false);
						}

						// -----------------------------------------------------------------------------------------
						// Create tree Node classes
						// -----------------------------------------------------------------------------------------

						if ($table->treeMode()) {
							switch($table->treeMode()) {
								case 'NestedSet':
									foreach (array('nestedsetpeer', 'nestedset') as $target) {
										$builder = $generatorConfig->getConfiguredBuilder($table, $target);
										$this->build($builder);
									}
								break;

								case 'MaterializedPath':
									foreach (array('nodepeer', 'node') as $target) {
										$builder = $generatorConfig->getConfiguredBuilder($table, $target);
										$this->build($builder);
									}

									foreach (array('nodepeerstub', 'nodestub') as $target) {
										$builder = $generatorConfig->getConfiguredBuilder($table, $target);
										$this->build($builder, $overwrite=false);
									}
								break;

								case 'AdjacencyList':
									// No implementation for this yet.
								default:
								break;
							}

						} // if Table->treeMode()


					} // if !$table->isForReferenceOnly()

				} // foreach table

			} // foreach database

		} // foreach dataModel

	} // main()
}
