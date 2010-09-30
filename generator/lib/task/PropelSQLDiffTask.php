<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/AbstractPropelDataModelTask.php';
require_once dirname(__FILE__) . '/../builder/om/ClassTools.php';
require_once dirname(__FILE__) . '/../builder/om/OMBuilder.php';
require_once dirname(__FILE__) . '/../model/diff/PropelDatabaseComparator.php';

/**
 * This Task creates the OM classes based on the XML schema file.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.task
 */
class PropelSQLDiffTask extends AbstractPropelDataModelTask
{
	protected $databaseName;
	
	/**
	 * Gets the datasource name.
	 *
	 * @return     string
	 */
	public function getDatabaseName()
	{
		return $this->databaseName;
	}

	/**
	 * Sets the datasource name.
	 *
	 * This will be used as the <database name=""> value in the generated schema.xml
	 *
	 * @param      string $v
	 */
	public function setDatabaseName($v)
	{
		$this->databaseName = $v;
	}
	
	/**
	 * Main method builds all the targets for a typical propel project.
	 */
	public function main()
	{
		// check to make sure task received all correct params
		$this->validate();
		
		$generatorConfig = $this->getGeneratorConfig();
		$platform = $generatorConfig->getConfiguredPlatform($con);
		
		// loading model from database
		$this->log('Reading databases structure...');
		$connections = $generatorConfig->getBuildConnections();
		if (!$connections) {
			throw new Exception('You must define database connection settings in a buildtime-conf.xml file to use diff');
		}
		$totalNbTables = 0;
		$ad = new AppData();
		foreach ($connections as $name => $params) {
			$this->log(sprintf('Connectig to database "%s" using DSN "%s"', $name, $params['dsn']), Project::MSG_VERBOSE);
			$pdo = $generatorConfig->getBuildPDO($name);
			$database = new Database($name);
			$platform = $generatorConfig->getConfiguredPlatform($pdo);
			$database->setPlatform($platform);
			$database->setDefaultIdMethod(IDMethod::NATIVE);
			$parser = $generatorConfig->getConfiguredSchemaParser($pdo);
			$nbTables = $parser->parse($database, $this);
			$ad->addDatabase($database);
			$totalNbTables += $nbTables;
			$this->log(sprintf('%d tables imported from databae "%s"', $nbTables, $name), Project::MSG_VERBOSE);
		}
		$this->log(sprintf('%d tables imported from databases.', $totalNbTables));

		
		// loading model from XML
		$this->packageObjectModel = true;
		$appDatasFromXml = $this->getDataModels();
		$appDataFromXml = array_pop($appDatasFromXml);
		
		// comparing models
		$this->log('Comparing models...');
		foreach ($ad->getDatabases() as $database) {
			$name = $database->getName();
			$this->log(sprintf('Comparing database "%s"', $name), Project::MSG_VERBOSE);
			if (!$appDataFromXml->hasDatabase($name)) {
				// FIXME: tables present in database but not in XML
				continue;
			}
			$databaseDiff = PropelDatabaseComparator::computeDiff($database, $appDataFromXml->getDatabase($name));
			
			if (!$databaseDiff) {
				$this->log('Same XML and database structures - no diff to generate', Project::MSG_VERBOSE);
				continue;
			}
		
			$messages = array();
			if ($count = $databaseDiff->countAddedTables()) {
				$messages []= sprintf('%d added tables', $count);
			}
			if ($count = $databaseDiff->countRemovedTables()) {
				$messages []= sprintf('%d removed tables', $count);
			}
			if ($count = $databaseDiff->countModifiedTables()) {
				$messages []= sprintf('%d modified tables', $count);
			}
			if ($count = $databaseDiff->countRenamedTables()) {
				$messages []= sprintf('%d renamed tables', $count);
			}
			$this->log(sprintf('Structure of database "%s" was modified: %s', $name, implode(', ', $messages)));
			
			echo $platform->getModifyDatabaseDDL($databaseDiff);
		}
	}
}