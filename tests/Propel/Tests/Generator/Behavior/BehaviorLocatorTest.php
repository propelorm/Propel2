<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior;

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Util\BehaviorLocator;
use Propel\Tests\TestCase;

/**
 * Tests the table structure behavior hooks.
 *
 * @author Thomas Gossmann
 */
class BehaviorLocatorTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        require_once(__DIR__ . '/../../../../Fixtures/behavior-installer/src/gossi/propel/behavior/l10n/L10nBehavior.php');
        require_once(__DIR__ . '/../../../../Fixtures/behavior-development/src/CollectionBehavior.php');
    }

    /**
     * @return void
     */
    public function testBehaviorLocatorWithComposerLock()
    {
        $locator = $this->getLocatorForLocation(__DIR__ . '/../../../../Fixtures/behavior-installer');
        $behaviors = $locator->getBehaviors();

        $this->assertCount(2, $behaviors);

        $this->assertArrayHasKey('l10n', $behaviors);
        $this->assertEquals('gossi/propel-l10n-behavior', $behaviors['l10n']['package']);
        $this->assertEquals('\\gossi\\propel\\behavior\\l10n\\L10nBehavior', $locator->getBehavior('l10n'));

        $this->assertArrayHasKey('le-dev-behavior', $behaviors);
    }

    /**
     * @return void
     */
    public function testBehaviorLocatorWithComposerJson()
    {
        $locator = $this->getLocatorForLocation(__DIR__ . '/../../../../Fixtures/behavior-development');
        $behaviors = $locator->getBehaviors();

        $this->assertSame(1, count($behaviors));

        $this->assertTrue(array_key_exists('collection', $behaviors));
        $this->assertSame('propel/collection-behavior', $behaviors['collection']['package']);

        // test class name
        $this->assertSame('\\Propel\\Behavior\\Collection\\CollectionBehavior', $locator->getBehavior('collection'));
    }

    /**
     * @return void
     */
    public function testBehaviorLocatorSkipsMissingSectionsInLockfile()
    {
        $locator = $this->getLocatorForLocation(__DIR__ . '/../../Resources/dummy_composer_lock_without_packages-dev');
        $behaviors = $locator->getBehaviors();

        $this->assertIsArray($behaviors);
        $this->assertCount(1, $behaviors);
        $this->assertArrayHasKey('l10n', $behaviors);
    }

    /**
     * @param string $composerDirPath
     *
     * @return \Propel\Generator\Util\BehaviorLocator behaviors
     */
    private function getLocatorForLocation(string $composerDirPath)
    {
        $configOptions['propel']['paths']['composerDir'] = $composerDirPath;
        $config = new QuickGeneratorConfig($configOptions);

        return new BehaviorLocator($config);
    }
}
