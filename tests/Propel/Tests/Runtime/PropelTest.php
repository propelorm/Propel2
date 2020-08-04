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

class PropelTest extends BaseTestCase
{
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
        $oldSC = Propel::getServiceContainer();
        $newSC = new StandardServiceContainer();
        Propel::setServiceContainer($newSC);
        $this->assertSame($newSC, Propel::getServiceContainer());
        Propel::setServiceContainer($oldSC);
    }
}
