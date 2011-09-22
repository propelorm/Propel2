<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../config/GeneratorConfigInterface.php';

/**
 * Service class for managing SQL.
 *
 * @author     William Durand <william.durand1@gmail.com>
 * @version    $Revision$
 * @package    propel.generator.util
 */
class PropelSqlManager
{
	/**
	 * @var GeneratorConfigInterface
	 */
	protected $generatorConfig;
	/**
	 * @var array
	 */
	protected $dataModels;
	/**
	 * @var array
	 */
	protected $databases = null;
	/**
	 * @var string
	 */
	protected $outputDir;

	/**
	 * @param GeneratorConfigInterface $generatorConfig
	 */
	public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
	{
		$this->generatorConfig = $generatorConfig;
	}

	/**
	 * @return GeneratorConfigInterface
	 */
	public function getGeneratorConfig()
	{
		return $this->generatorConfig;
	}

	/**
	 * @param array $dataModels
	 */
	public function setDataModels($dataModels)
	{
		$this->dataModels = $dataModels;
	}

	/**
	 * @return array
	 */
	public function getDataModels()
	{
		return $this->dataModels;
	}

	/**
	 * @param string $outputDir
	 */
	public function setOutputDir($outputDir)
	{
		$this->outputDir = $outputDir;
	}

	/**
	 * return string
	 */
	public function getOutputDir()
	{
		return $this->outputDir;
	}

	/**
	 * @return array
	 */
	public function getDatabases()
	{
		if (null === $this->databases) {
			$databases = array();
			foreach ($this->getDataModels() as $package => $dataModel) {
				foreach ($dataModel->getDatabases() as $database) {
					if (!isset($databases[$database->getName()])) {
						$databases[$database->getName()] = $database;
					} else {
						$tables = $database->getTables();
						// Merge tables from different schema.xml to the same database
						foreach ($tables as $table) {
							if (!$databases[$database->getName()]->hasTable($table->getName(), true)) {
								$databases[$database->getName()]->addTable($table);
							}
						}
					}
				}
			}
			$this->databases = $databases;
		}
		return $this->databases;
	}

	/**
	 * Build SQL files.
	 */
	public function buildSql()
	{
		$sqlDbMapContent = "# Sqlfile -> Database map\n";
		foreach ($this->getDatabases() as $databaseName => $database) {
			$platform = $database->getPlatform();
			$filename = $database->getName() . '.sql';

			if ($this->getGeneratorConfig()->getBuildProperty('disableIdentifierQuoting')) {
				$platform->setIdentifierQuoting(false);
			}

			$ddl = $platform->getAddTablesDDL($database);

			$file = $this->getOutputDir() . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($file) && $ddl == file_get_contents($file)) {
				// Unchanged
			} else {
				file_put_contents($file, $ddl);
			}

			$sqlDbMapContent .= sprintf("%s=%s\n", $filename, $databaseName);
		}

		file_put_contents ($this->getOutputDir() . DIRECTORY_SEPARATOR . 'sqldb.map', $sqlDbMapContent);
	}
}
