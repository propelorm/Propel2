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
        $configOptions['propel']['paths']['composerDir'] = __DIR__ . '/../../../../Fixtures/behavior-installer';
        $config = new QuickGeneratorConfig($configOptions);
        $locator = new BehaviorLocator($config);

        // test found behaviors
        $behaviors = $locator->getBehaviors();
        $this->assertSame(1, count($behaviors));

        $this->assertTrue(array_key_exists('l10n', $behaviors));
        $this->assertSame('gossi/propel-l10n-behavior', $behaviors['l10n']['package']);

        // test class name
        $this->assertSame('\\gossi\\propel\\behavior\\l10n\\L10nBehavior', $locator->getBehavior('l10n'));
    }

    /**
     * @return void
     */
    public function testBehaviorLocatorWithComposerJson()
    {
        $configOptions['propel']['paths']['composerDir'] = __DIR__ . '/../../../../Fixtures/behavior-development';
        $config = new QuickGeneratorConfig($configOptions);
        $locator = new BehaviorLocator($config);

        // test found behaviors
        $behaviors = $locator->getBehaviors();
        $this->assertSame(1, count($behaviors));

        $this->assertTrue(array_key_exists('collection', $behaviors));
        $this->assertSame('propel/collection-behavior', $behaviors['collection']['package']);

        // test class name
        $this->assertSame('\\Propel\\Behavior\\Collection\\CollectionBehavior', $locator->getBehavior('collection'));
    }
}
