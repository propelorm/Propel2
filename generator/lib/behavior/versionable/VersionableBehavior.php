<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/VersionableBehaviorObjectBuilderModifier.php';

/**
 * Keeps tracks of all the modifications in an ActiveRecord object
 *
 * @author    Francois Zaninotto
 * @version		$Revision$
 * @package		propel.generator.behavior.versionable
 */
class VersionableBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'version_column' => 'version',
	);

	protected $objectBuilderModifier;
	
	/**
	 * Add the version_column to the current table
	 */
	public function modifyTable()
	{
		if(!$this->getTable()->containsColumn($this->getParameter('version_column'))) {
			$this->getTable()->addColumn(array(
				'name' => $this->getParameter('version_column'),
				'type' => 'INTEGER',
				'default' => 0
			));
		}
	}

	public function getObjectBuilderModifier()
	{
		if (is_null($this->objectBuilderModifier))
		{
			$this->objectBuilderModifier = new VersionableBehaviorObjectBuilderModifier($this);
		}
		return $this->objectBuilderModifier;
	}
	
}
