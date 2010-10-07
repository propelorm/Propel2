<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'phing/Task.php';
require_once dirname(__FILE__) . '/../util/PropelMigrationManager.php';

/**
 * This Task executes the next migrations up
 *
 * @author     Francois Zaninotto
 * @package    propel.generator.task
 */
class PropelMigrationTask extends Task
{
	/**
	 * Destination directory for results of template scripts.
	 * @var        PhingFile
	 */
	protected $outputDirectory;
	
	/**
	 * An initialized GeneratorConfig object containing the converted Phing props.
	 *
	 * @var        GeneratorConfig
	 */
	protected $generatorConfig;
	
	/**
	 * The migration table name
	 * @var string
	 */
	protected $migrationTable = 'propel_migration';
	
	/**
	 * Set the migration Table name
	 *
	 * @param string $migrationTable
	 */
	public function setMigrationTable($migrationTable)
	{
		$this->migrationTable = $migrationTable;
	}

	/**
	 * Get the migration Table name
	 *
	 * @return string
	 */
	public function getMigrationTable()
	{
		return $this->migrationTable;
	}
	
	/**
	 * [REQUIRED] Set the output directory. It will be
	 * created if it doesn't exist.
	 * @param      PhingFile $outputDirectory
	 * @return     void
	 * @throws     Exception
	 */
	public function setOutputDirectory(PhingFile $outputDirectory) {
		try {
			if (!$outputDirectory->exists()) {
				$this->log("Output directory does not exist, creating: " . $outputDirectory->getPath(),Project::MSG_VERBOSE);
				if (!$outputDirectory->mkdirs()) {
					throw new IOException("Unable to create Ouptut directory: " . $outputDirectory->getAbsolutePath());
				}
			}
			$this->outputDirectory = $outputDirectory->getCanonicalPath();
		} catch (IOException $ioe) {
			throw new BuildException($ioe);
		}
	}

	/**
	 * Get the output directory.
	 * @return     string
	 */
	public function getOutputDirectory() {
		return $this->outputDirectory;
	}
	
	/**
	 * Gets the GeneratorConfig object for this task or creates it on-demand.
	 * @return     GeneratorConfig
	 */
	protected function getGeneratorConfig()
	{
		if ($this->generatorConfig === null) {
			$this->generatorConfig = new GeneratorConfig();
			$this->generatorConfig->setBuildProperties($this->getProject()->getProperties());
		}
		return $this->generatorConfig;
	}
	
	
	/**
	 * Main method builds all the targets for a typical propel project.
	 */
	public function main()
	{
		$manager = new PropelMigrationManager();
		$manager->setConnections($this->getGeneratorConfig()->getBuildConnections());
		$manager->setMigrationTable($this->getMigrationTable());
		$manager->setMigrationDir($this->getOutputDirectory());
		
		if (!$nextMigrationTimestamp = $manager->getFirstUpMigrationTimestamp()) {
			$this->log('All migrations were already executed - nothing to migrate.');
			return false;
		}
		
		$timestamps = $manager->getValidMigrationTimestamps();
		if (count($timestamps) > 1) {
			$this->log(sprintf('%d migrations to execute', count($timestamps)));
		}
		foreach ($timestamps as $timestamp) {
			$migration = $manager->getMigrationObject($timestamp);
			$this->log(sprintf('Executing migration %s', $manager->getMigrationClassName($timestamp)));
			foreach ($migration->getUpSQL() as $datasource => $sql) {
				$pdo = $manager->getPdoConnection($datasource);
				$res = 0;
				$statements = PropelSQLParser::parseString($sql);
				foreach ($statements as $statement) {
					try {
						$this->log(sprintf('  Executing statement "%s"', $statement), Project::MSG_VERBOSE);
						$stmt = $pdo->prepare($statement);
						$stmt->execute();
						$res++;
					} catch (PDOException $e) {
						$this->log(sprintf('Failed to execute SQL "%s"', $statement), Project::MSG_ERR);
						// continue
					}
				}
				if (!$res) {
					$this->log('No statement was executed. The version was not updated.');
					$this->log(sprintf(
						'Please review the code in "%s"', 
						$manager->getMigrationDir() . DIRECTORY_SEPARATOR . $manager->getMigrationClassName($timestamp)
					));
					$this->log('Migration aborted', Project::MSG_ERR);
					return false;
				}
				$this->log(sprintf(
					'%d of %d SQL statements executed successfully on datasource "%s"',
					$res,
					count($statements),
					$datasource
				));
				$manager->updateLatestMigrationTimestamp($datasource, $timestamp);
				$this->log(sprintf(
					'Updated latest migration date to %d for datasource "%s"',
					$timestamp,
					$datasource
				), Project::MSG_VERBOSE);
			}
		}
		
		$this->log('Migration complete. No further migration to execute.');
	}
}