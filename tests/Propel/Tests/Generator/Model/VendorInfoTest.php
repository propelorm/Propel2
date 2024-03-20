<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\VendorInfo;
use Propel\Tests\TestCase;

/**
 * Unit test suite for the VendorInfo model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class VendorInfoTest extends TestCase
{
    /**
     * @return void
     */
    public function testSetupObject()
    {
        $info = new VendorInfo();
        $info->loadMapping(['type' => 'foo']);

        $this->assertSame('foo', $info->getType());
    }

    /**
     * @return void
     */
    public function testGetSetType()
    {
        $info = new VendorInfo('foo');

        $this->assertSame('foo', $info->getType());
        $this->assertTrue($info->isEmpty());
    }

    /**
     * @return void
     */
    public function testSetParameter()
    {
        $info = new VendorInfo();
        $info->setParameter('foo', 'bar');

        $this->assertFalse($info->isEmpty());
        $this->assertTrue($info->hasParameter('foo'));
        $this->assertSame('bar', $info->getParameter('foo'));
    }

    /**
     * @return void
     */
    public function testSetParameters()
    {
        $info = new VendorInfo();
        $info->setParameters(['foo' => 'bar', 'baz' => 'bat']);

        $this->assertFalse($info->isEmpty());
        $this->assertArrayHasKey('foo', $info->getParameters());
        $this->assertArrayHasKey('baz', $info->getParameters());
    }

    /**
     * @return void
     */
    public function testMergeVendorInfo()
    {
        $current = new VendorInfo('mysql');
        $current->setParameters(['foo' => 'bar', 'baz' => 'bat']);

        $toMerge = new VendorInfo('mysql');
        $toMerge->setParameters(['foo' => 'wat', 'int' => 'mix']);

        $merged = $current->getMergedVendorInfo($toMerge);

        $this->assertInstanceOf('Propel\Generator\Model\VendorInfo', $merged);

        $this->assertSame('wat', $merged->getParameter('foo'));
        $this->assertSame('bat', $merged->getParameter('baz'));
        $this->assertSame('mix', $merged->getParameter('int'));
    }
}
