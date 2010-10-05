<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'phing/Task.php';

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
	
	protected function getOldestDatabaseVersion()
	{
		$generatorConfig = $this->getGeneratorConfig();
		$connections = $generatorConfig->getBuildConnections();
		if (!$connections) {
			throw new Exception('You must define database connection settings in a buildtime-conf.xml file to use migrations');
		}
		$oldestMigrationTimestamp = null;
		$migrationTimestamps = array();
		foreach ($connections as $name => $params) {
			$pdo = $generatorConfig->getBuildPDO($name);
			$sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());
			$stmt = $pdo->prepare($sql);
			try {
				$stmt->execute();
				if ($migrationTimestamp = $stmt->fetchColumn()) {
					$migrationTimestamps[$name] = $migrationTimestamp;
				}
			} catch (PDOException $e) {
				$this->log(sprintf('Creating %s table in database "%s"', $this->getMigrationTable(), $name), Project::MSG_VERBOSE);
				$sql = sprintf('CREATE TABLE %s (version INTEGER DEFAULT 0)', $this->getMigrationTable());
				$stmt = $pdo->prepare($sql);
				if (!$stmt->execute()) {
					throw new Exception('Unable to create migration table');
				}
				$oldestMigrationTimestamp = 0;
			}
		}
		if ($oldestMigrationTimestamp === null && $migrationTimestamps) {
			sort($migrationTimestamps);
			$oldestMigrationTimestamp = array_shift($migrationTimestamps);
		}
		
		return $oldestMigrationTimestamp;
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
		$connections = $generatorConfig->getBuildConnections();
		if (!$connections) {
			throw new Exception('You must define database connection settings in a buildtime-conf.xml file to use migrations');
		}
		
		$this->log('Checking Database Versions...');
		if ($oldestMigrationTimestamp = $this->getOldestDatabaseVersion()) {
			$this->log(sprintf(
				'Oldest migration was achieved on %s (timestamp %d)', 
				date('Y-m-d H:i:s', $oldestMigrationTimestamp),
				$oldestMigrationTimestamp)
			, Project::MSG_VERBOSE);
		} else {
			$this->log('No migration was ever executed on these connection settings.', Project::MSG_VERBOSE);
		}

		$this->log('Listing Migration files...');
		$migrationTimestamps = $this->getMigrationTimestamps($this->getOutputDirectory());
		if (!$migrationTimestamps) {
			$this->log('No migration file found. Make sure you run the sql-diff task.');
			return false;
		}
		
		// removing already executed migrations
		sort($migrationTimestamps);
		foreach ($migrationTimestamps as $key => $value) {
			if ($value <= $oldestMigrationTimestamp) {
				$this->log(sprintf('Migration "PropelMigration_%d.php" was already executed', $value), Project::MSG_VERBOSE);
				unset($migrationTimestamps[$key]);
			}
		}
		
		if (!$migrationTimestamps) {
			$this->log('All %d migration files were already executed - Nothing to migrate.');
			return false;
		}
		
		$this->log('Some migration files need to be executed:');
		foreach ($migrationTimestamps as $timestamp) {
			$this->log(sprintf('  PropelMigration_%d.php',$timestamp));
		}
		$this->log('Call the "migrate" task to execute these migrations');
	}
}