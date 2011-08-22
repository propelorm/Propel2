<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Keeps tracks of an ActiveRecord object, even after deletion
 *
 * @author     François Zaninotto
 * @package    propel.generator.behavior.archivable
 */
class ArchivableBehaviorQueryBuilderModifier
{
	protected $behavior, $table;

	public function __construct($behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function queryMethods($builder)
	{
		$script = '';
		$script .= $this->addArchive($builder);
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	protected function addArchive($builder)
	{
		$archiveTablePhpName = $this->behavior->getArchiveTablePhpName($builder);
		return "
/**
 * Copy the data of the objects satisfying the query into $archiveTablePhpName archive objects.
 * The archived objects are then saved.
 * If any of the objects has already been archived, the archived object
 * is updated and not duplicated.
 * Warning: This termination methods issues 2n+1 queries.
 *
 * @param      PropelPDO \$con	Connection to use.
 * @param      Boolean \$useLittleMemory	Whether or not to use PropelOnDemandFormatter to retrieve objects.
 *               Set to false if the identity map matters.
 *               Set to true (default) to use less memory.
 *
 * @return     int the number of archived objects
 */
public function archive(\$con = null, \$useLittleMemory = true)
{
	\$totalArchivedObjects = 0;
	\$criteria = clone \$this;
	// prepare the query
	\$criteria->setWith(array());
	if (\$useLittleMemory) {
		\$criteria->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
	}
	// archive all results one by one
	foreach (\$criteria->find(\$con) as \$object) {
		\$object->archive(\$con);
		\$totalArchivedObjects++;
	}
	
	return \$totalArchivedObjects;
}
";
	}

}
