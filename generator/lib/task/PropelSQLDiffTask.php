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
		$this->log('Reading database structure...');
		$con = $this->getConnection();
		$database = new Database($this->getDatabaseName());
		$database->setPlatform($platform);
		$database->setDefaultIdMethod(IDMethod::NATIVE);
		$parser = $generatorConfig->getConfiguredSchemaParser($con);
		$nbTables = $parser->parse($database, $this);
		$this->log(sprintf('%d tables found.', $nbTables));
		
		// loading model from XML
		$this->packageObjectModel = true;
		$appDatasFromXml = $this->getDataModels();
		$appDataFromXml = array_pop($appDatasFromXml);
		
		// comparing models
		$this->log('Comparing models...');
		$databaseDiff = PropelDatabaseComparator::computeDiff($database, $appDataFromXml->getDatabase());
		if (!$databaseDiff) {
			$this->log('Same XML and database structures - no diff to generate');
			return;
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
		$this->log(sprintf('Structure was modified: %s', implode(', ', $messages)));
		echo $platform->getModifyDatabaseDDL($databaseDiff);
	}
}