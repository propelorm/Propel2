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
 * This Task creates the OM classes based on the XML schema file.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.task
 */
class PropelMigrationStatusTask extends Task
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
	
	
	protected function getMigrationTimestamps($path)
	{
		$migrationTimestamps = array();
		$migrationsDir = new PhingFile($path);
		$files = $migrationsDir->listFiles();
		foreach ($files as $file) {
			$fileName = $file->getName();
			if (preg_match('/^PropelMigration_(\d+)\.php$/', $fileName, $matches)) {
				$this->log(sprintf('File "%s" is a valid migration class', $fileName), Project::MSG_VERBOSE);
				$migrationTimestamps[] = (integer) $matches[1];
			}
		}
		
		return $migrationTimestamps;
	}
	
	/**
	 * Main method builds all the targets for a typical propel project.
	 */
	public function main()
	{
		$generatorConfig = $this->getGeneratorConfig();
		$manager = new PropelMigrationManager();
		$manager->setConnections($generatorConfig->getBuildConnections());
		$manager->setMigrationTable($this->getMigrationTable());
		$manager->setMigrationDir($this->getOutputDirectory());
		
		// the following is a verbose version of PropelMigrationManager::getValidMigrationTimestamps()
		// mostly for explicit output
		
		$this->log('Checking Database Versions...');
		foreach ($manager->getConnections() as $name => $params) {
			if (!$manager->migrationTableExists($name)) {
				$this->log(sprintf(
					'Migration table does not exist in datasource "%s"; creating it.', 
					$name
				), Project::MSG_VERBOSE);
				$manager->createMigrationTable($name);
			}
		}
		
		if ($oldestMigrationTimestamp = $manager->getOldestDatabaseVersion()) {
			$this->log(sprintf(
				'Oldest migration was achieved on %s (timestamp %d)', 
				date('Y-m-d H:i:s', $oldestMigrationTimestamp),
				$oldestMigrationTimestamp
			), Project::MSG_VERBOSE);
		} else {
			$this->log('No migration was ever executed on these connection settings.', Project::MSG_VERBOSE);
		}

		$this->log('Listing Migration files...');
		$dir = $this->getOutputDirectory();
		$migrationTimestamps = $manager->getMigrationTimestamps();
		$nbExistingMigrations = count($migrationTimestamps);
		if ($migrationTimestamps) {
			$this->log(sprintf(
				'%d valid migration classes found in "%s"',
				$nbExistingMigrations,
				$dir
			), Project::MSG_VERBOSE);
		} else {
			$this->log(sprintf('No migration file found in "%s". Make sure you run the sql-diff task.', $dir));
			return false;
		}
		$migrationTimestamps = $manager->getValidMigrationTimestamps();
		$nbNotYetExecutedMigrations = count($migrationTimestamps);
		if (!$nbNotYetExecutedMigrations) {
			$this->log('All migration files were already executed - Nothing to migrate.');
			return false;
		} elseif ($nbExecutedMigrations = $nbExistingMigrations - $nbNotYetExecutedMigrations) {
			$this->log(sprintf(
				'%d migrations were already executed',
				$nbExecutedMigrations
			), Project::MSG_VERBOSE);
		}
		
		$this->log('Some migrations need to be executed:');
		foreach ($migrationTimestamps as $timestamp) {
			$this->log(sprintf('  %s', $manager->getMigrationClassName($timestamp)));
		}
		$this->log('Call the "migrate" task to execute them');
	}
}