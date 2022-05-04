<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime;

use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Propel\Tests\Helpers\BaseTestCase;
use Propel\Runtime\Exception\PropelException;

class PropelTest extends BaseTestCase
{
    protected static $initialServiceContainer;
    
    public static function setUpBeforeClass(): void
    {
        static::$initialServiceContainer = Propel::getServiceContainer();
    }
    
    public function tearDown(): void
    {
        Propel::setServiceContainer(static::$initialServiceContainer);
    }
    
    /**
     * @return void
     */
    public function testGetServiceContainerReturnsAServiceContainer()
    {
        $this->assertInstanceOf(ServiceContainerInterface::class, Propel::getServiceContainer());
    }

    /**
     * @return void
     */
    public function testGetServiceContainerAlwaysReturnsTheSameInstance()
    {
        $sc1 = Propel::getServiceContainer();
        $sc1->foo = 'bar';
        $sc2 = Propel::getServiceContainer();
        $this->assertSame($sc1, $sc2);
    }

    /**
     * @return void
     */
    public function testSetServiceContainerOverridesTheExistingServiceContainer()
    {
        $newSC = new StandardServiceContainer();
        Propel::setServiceContainer($newSC);
        $this->assertSame($newSC, Propel::getServiceContainer());
    }
    
    public function testGetStandardServiceContainerWithDefaultContainer()
    {
        $sc = Propel::getStandardServiceContainer();
        $this->assertInstanceOf(StandardServiceContainer::class, $sc);
    }
    
    
    public function testGetStandardServiceContainerThrowsErrorWithNonStandardContainer()
    {
        $sc = $this->createMock(ServiceContainerInterface::class);
        Propel::setServiceContainer($sc);
        $this->expectException(PropelException::class);
        Propel::getStandardServiceContainer();
    }
    
}
