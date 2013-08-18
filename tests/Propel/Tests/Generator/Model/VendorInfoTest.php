<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
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
    public function testSetupObject()
    {
        $info = new VendorInfo();
        $info->loadMapping(array('type' => 'foo'));

        $this->assertSame('foo', $info->getType());
    }

    public function testGetSetType()
    {
        $info = new VendorInfo('foo');

        $this->assertSame('foo', $info->getType());
        $this->assertTrue($info->isEmpty());
    }

    public function testSetParameter()
    {
        $info = new VendorInfo();
        $info->setParameter('foo', 'bar');

        $this->assertFalse($info->isEmpty());
        $this->assertTrue($info->hasParameter('foo'));
        $this->assertSame('bar', $info->getParameter('foo'));
    }

    public function testSetParameters()
    {
        $info = new VendorInfo();
        $info->setParameters(array('foo' => 'bar', 'baz' => 'bat'));

        $this->assertFalse($info->isEmpty());
        $this->assertArrayHasKey('foo', $info->getParameters());
        $this->assertArrayHasKey('baz', $info->getParameters());
    }

    public function testMergeVendorInfo()
    {
        $current = new VendorInfo('mysql');
        $current->setParameters(array('foo' => 'bar', 'baz' => 'bat'));

        $toMerge = new VendorInfo('mysql');
        $toMerge->setParameters(array('foo' => 'wat', 'int' => 'mix'));

        $merged = $current->getMergedVendorInfo($toMerge);

        $this->assertInstanceOf('Propel\Generator\Model\VendorInfo', $merged);

        $this->assertSame('wat', $merged->getParameter('foo'));
        $this->assertSame('bat', $merged->getParameter('baz'));
        $this->assertSame('mix', $merged->getParameter('int'));
    }
}
