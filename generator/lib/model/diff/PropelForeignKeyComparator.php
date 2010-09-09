<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license     MIT License
 */

require_once dirname(__FILE__) . '/../ForeignKey.php';

/**
 * Service class for comparing ForeignKey objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 *
 * @package     propel.generator.model.diff
 */
class PropelForeignKeyComparator
{
	/**
	 * Compute the difference between two Foreign key objects
	 *
	 * @param ForeignKey $fromFk
	 * @param ForeignKey $toFk
	 *
	 * @return boolean false if the two fks are similar, true if they have differences
	 */
	static public function computeDiff(ForeignKey $fromFk, ForeignKey $toFk)
	{
		// Check for differences in local and remote table
		if ($fromFk->getTableName() != $toFk->getTableName()) {
			return true;
		}
		if ($fromFk->getForeignTableName() != $toFk->getForeignTableName()) {
			return true;
		}
		
		// compare columns
		if (array_map('strtolower', $fromFk->getLocalColumns()) != array_map('strtolower', $toFk->getLocalColumns())) {
			return true;
		}
		if (array_map('strtolower', $fromFk->getForeignColumns()) != array_map('strtolower', $toFk->getForeignColumns())) {
			return true;
		}
		
		// compare on
		if ($fromFk->normalizeFKey($fromFk->getOnUpdate()) != $toFk->normalizeFKey($toFk->getOnUpdate())) {
			return true;
		}
		if ($fromFk->normalizeFKey($fromFk->getOnDelete()) != $toFk->normalizeFKey($toFk->getOnDelete())) {
			return true;
		}
		
		return false;
	}
	
}