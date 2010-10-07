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
require_once dirname(__FILE__) . '/../util/PropelMigrationManager.php';

/**
 * This Task creates the OM classes based on the XML schema file.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.task
 */
class PropelSQLDiffTask extends AbstractPropelDataModelTask
{
	protected $databaseName;
	protected $editorCmd;
	
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
	 * Setter for the editorCmd property
	 *
	 * @param string $editorCmd
	 */
	public function setEditorCmd($editorCmd)
	{
		$this->editorCmd = $editorCmd;
	}

	/**
	 * Getter for the editorCmd property
	 *
	 * @return string
	 */
	public function getEditorCmd()
	{
		return $this->editorCmd;
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
			$this->log(sprintf('Connecting to database "%s" using DSN "%s"', $name, $params['dsn']), Project::MSG_VERBOSE);
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
		$migrationsUp = array();
		$migrationsDown = array();
		foreach ($ad->getDatabases() as $database) {
			$name = $database->getName();
			$this->log(sprintf('Comparing database "%s"', $name), Project::MSG_VERBOSE);
			if (!$appDataFromXml->hasDatabase($name)) {
				// FIXME: tables present in database but not in XML
				continue;
			}
			$databaseDiff = PropelDatabaseComparator::computeDiff($database, $appDataFromXml->getDatabase($name));
			
			if (!$databaseDiff) {
				$this->log(sprintf('Same XML and database structures for datasource "%s" - no diff to generate', $name), Project::MSG_VERBOSE);
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
			$this->log(sprintf('Structure of database was modified in datasource "%s": %s', $name, implode(', ', $messages)));
			
			$migrationsUp[$name] = $platform->getModifyDatabaseDDL($databaseDiff);
			$migrationsDown[$name] = $platform->getModifyDatabaseDDL($databaseDiff->getReverseDiff());
		}
		
		if (!$migrationsUp) {
			$this->log('Same XML and database structures for all datasource - no diff to generate');
			return;
		}
		
		$time = time();
		$timeInWords = date('Y-m-d H:i:s', $time);
		$migrationAuthor = ($author = PropelMigrationManager::getUser()) ? 'by ' . $author : '';
		$migrationClassName = sprintf('PropelMigration_%d', $time);
		$migrationFileName = sprintf('%s.php', $migrationClassName);
		$migrationUpString = var_export($migrationsUp, true);
		$migrationDownString = var_export($migrationsDown, true);
		$migrationClassBody = <<<EOP
<?php

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version $time.
 * Generated on $timeInWords $migrationAuthor
 */
class $migrationClassName
{
	
	public function preUp(\$manager)
	{
		// add the pre-migration code here
	}

	public function postUp(\$manager)
	{
		// add the post-migration code here
	}

	public function preDown(\$manager)
	{
		// add the pre-migration code here
	}

	public function postDown(\$manager)
	{
		// add the post-migration code here
	}
	
	/**
	 * Get the SQL statements for the Up migration
	 *
	 * @return array list of the SQL strings to execute for the Up migration
	 *               the keys being the datasources
	 */
	public function getUpSQL()
	{
		return $migrationUpString;
	}

	/**
	 * Get the SQL statements for the Down migration
	 *
	 * @return array list of the SQL strings to execute for the Down migration
	 *               the keys being the datasources
	 */
	public function getDownSQL()
	{
		return $migrationDownString;
	}

}
EOP;

		$_f = new PhingFile($this->getOutputDirectory(), $migrationFileName);
		file_put_contents($_f->getAbsolutePath(), $migrationClassBody);
		$this->log(sprintf('"%s" file successfully created in %s', $_f->getName(), $_f->getParent()));
		if ($editorCmd = $this->getEditorCmd()) {
			$this->log(sprintf('Using "%s" as text editor', $editorCmd));
			shell_exec($editorCmd . ' ' . escapeshellarg($_f->getAbsolutePath()));
		} else {
			$this->log('  Please review the generated SQL statements, and add data migration code if necessary.');
			$this->log('  Once the migration class is valid, call the "migrate" task to execute it.');
		}
	}
}