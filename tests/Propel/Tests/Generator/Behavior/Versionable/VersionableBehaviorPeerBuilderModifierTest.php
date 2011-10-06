<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Behavior\Versionable;

use Propel\Generator\Util\PropelQuickBuilder;
use Propel\Generator\Behavior\Versionable\VersionableBehavior;

use Propel\Runtime\Propel;

/**
 * Tests for VersionableBehavior class
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    generator.behavior.versionable
 */
class VersionableBehaviorPeerBuilderModifierTest extends \PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		if (!class_exists('VersionableBehaviorTest10')) {
			$schema = <<<EOF
<database name="versionable_behavior_test_10">
	<table name="versionable_behavior_test_10">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="INTEGER" />
		<behavior name="versionable" />
	</table>
</database>>
EOF;
			PropelQuickBuilder::buildSchema($schema);
		}
	}

	public function testIsVersioningEnabled()
	{
		$this->assertTrue(VersionableBehaviorTest10Peer::isVersioningEnabled());
		VersionableBehaviorTest10Peer::disableVersioning();
		$this->assertFalse(VersionableBehaviorTest10Peer::isVersioningEnabled());
		VersionableBehaviorTest10Peer::enableVersioning();
		$this->assertTrue(VersionableBehaviorTest10Peer::isVersioningEnabled());
	}

}
