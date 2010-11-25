<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../builder/util/XmlToAppData.php';
require_once dirname(__FILE__) . '/PropelSQLParser.php';

class PropelQuickBuilder
{
	protected $schema, $platform, $config, $database;
	
	public function setSchema($schema)
	{
		$this->schema = $schema;
	}

	/**
	 * Setter for the platform property
	 *
	 * @param PropelPlatformInterface $platform
	 */
	public function setPlatform($platform)
	{
		$this->platform = $platform;
	}
	
	/**
	 * Getter for the platform property
	 *
	 * @return PropelPlatformInterface
	 */
	public function getPlatform()
	{
		if (null === $this->platform) {
			require_once dirname(__FILE__) . '/../platform/SqlitePlatform.php';
			$this->platform = new SqlitePlatform();
		}
		return $this->platform;
	}
	
	/**
	 * Setter for the config property
	 *
	 * @param GeneratorConfigInterface $config
	 */
	public function setConfig(GeneratorConfigInterface $config)
	{
		$this->config = $config;
	}

	/**
	 * Getter for the config property
	 *
	 * @return GeneratorConfigInterface
	 */
	public function getConfig()
	{
		if (null === $this->config) {
			require_once dirname(__FILE__) . '/../config/QuickGeneratorConfig.php';
			$this->config = new QuickGeneratorConfig();
		}
		return $this->config;
	}
	
	public static function buildSchema($schema, $dsn = null, $user = null, $pass = null, $adapter = null)
	{
		$builder = new self;
		$builder->setSchema($schema);
		return $builder->build($dsn, $user, $pass, $adapter);
	}
	
	public function build($dsn = null, $user = null, $pass = null, $adapter = null)
	{
		if (null === $dsn) {
			$dsn = 'sqlite::memory:';
		}
		if (null === $adapter) {
			$adapter = new DBSQLite();
		}
		$con = new PropelPDO($dsn, $user, $pass);
		$this->buildSQL($con);
		$this->buildClasses();
		$name = $this->getDatabase()->getName();
		Propel::setDB($name, $adapter);
		Propel::setConnection($name, $con);
		return $con;
	}
	
	public function getDatabase()
	{
		if (null === $this->database) {
			$xtad = new XmlToAppData($this->getPlatform());
			$appData = $xtad->parseString($this->schema);
			$this->database = $appData->getDatabase();
		}
		return $this->database;
	}
	
	public function buildSQL(PDO $con)
	{
		return PropelSQLParser::executeString($this->getSQL(), $con);
	}
	
	public function getSQL()
	{
		return $this->getPlatform()->getAddTablesDDL($this->getDatabase());
	}
	
	public function buildClasses()
	{
		eval($this->getClasses());
	}
	
	public function getClasses()
	{	
		$script = '';
		foreach ($this->getDatabase()->getTables() as $table) {
			
			foreach (array('tablemap', 'peer', 'object', 'query', 'peerstub', 'objectstub', 'querystub') as $target) {
				$script .= $this->getConfig()->getConfiguredBuilder($table, $target)->build();
			}
			
			if ($col = $table->getChildrenColumn()) {
				if ($col->isEnumeratedClasses()) {
					foreach ($col->getChildren() as $child) {
						if ($child->getAncestor()) {
							$builder = $this->getConfig()->getConfiguredBuilder('queryinheritance', $target);
							$builder->setChild($child);
							$script .= $builder->build();
						}
						foreach (array('objectmultiextend', 'queryinheritancestub') as $target) {
							$builder = $this->getConfig()->getConfiguredBuilder($table, $target);
							$builder->setChild($child);
							$script .= $builder->build();
						}
					}
				}
			}
			
			if ($table->getInterface()) {
				$script .= $this->getConfig()->getConfiguredBuilder('interface', $target)->build();
			}

			if ($table->treeMode()) {
				switch($table->treeMode()) {
					case 'NestedSet':
						foreach (array('nestedsetpeer', 'nestedset') as $target) {
							$script .= $this->getConfig()->getConfiguredBuilder($table, $target)->build();
						}
					break;
					case 'MaterializedPath':
						foreach (array('nodepeer', 'node') as $target) {
							$script .= $this->getConfig()->getConfiguredBuilder($table, $target)->build();
						}
						foreach (array('nodepeerstub', 'nodestub') as $target) {
							$script .= $this->getConfig()->getConfiguredBuilder($table, $target)->build();
						}
					break;
					case 'AdjacencyList':
						// No implementation for this yet.
					default:
					break;
				}
			}
			
			if ($table->hasAdditionalBuilders()) {
				foreach ($table->getAdditionalBuilders() as $builderClass) {
					$builder = new $builderClass($table);
					$script .= $builder->build();
				}
			}
		}
		
		// remove extra <?php
		$script = str_replace('<?php', '', $script);
		return $script;
	}
	
}