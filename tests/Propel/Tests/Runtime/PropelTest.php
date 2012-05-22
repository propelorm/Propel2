<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;

class PropelTest extends BaseTestCase
{
    public function testGetServiceContainerReturnsAServiceContainer()
    {
        $this->assertInstanceOf('\Propel\Runtime\ServiceContainer\ServiceContainerInterface', Propel::getServiceContainer());
    }

    public function testGetServiceContainerAlwaysReturnsTheSameInstance()
    {
        $sc1 = Propel::getServiceContainer();
        $sc1->foo = 'bar';
        $sc2 = Propel::getServiceContainer();
        $this->assertSame($sc1, $sc2);
    }

    public function testSetServiceContainerOverridesTheExistingServiceContainer()
    {
        $oldSC = Propel::getServiceContainer();
        $newSC = new StandardServiceContainer();
        Propel::setServiceContainer($newSC);
        $this->assertSame($newSC, Propel::getServiceContainer());
        Propel::setServiceContainer($oldSC);
    }
}
