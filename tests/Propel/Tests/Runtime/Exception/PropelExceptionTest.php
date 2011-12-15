<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\Exception;

use Propel\Runtime\Exception\PropelException;

/**
 * Test for PropelException class
 *
 * @author     Francois Zaninotto
 */
class PropelExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleConstructor()
    {
        $e = new PropelException('this is an error');
        $this->assertTrue($e instanceof \Exception);
        $this->assertEquals('this is an error', $e->getMessage());
    }

    public function testExceptionConstructor()
    {
        $e1 = new FooException('real cause');
        $e = new PropelException($e1);
        $this->assertEquals(' [wrapped: real cause]', $e->getMessage());
    }

    public function testCompositeConstructor()
    {
        $e1 = new FooException('real cause');
        $e = new PropelException('this is an error', $e1);
        $this->assertEquals('this is an error [wrapped: real cause]', $e->getMessage());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testIsThrowable()
    {
        $e = new PropelException('this is an error');
        throw $e;
    }

    public function testGetCause()
    {
        $e1 = new FooException('real cause');
        $e = new PropelException('this is an error', $e1);
        $this->assertEquals($e1, $e->getCause());
    }

    public function testGetPrevious()
    {
        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
            $this->markTestSkipped();
        }
        $e1 = new FooException('real cause');
        $e = new PropelException('this is an error', $e1);
        $this->assertEquals($e1, $e->getPrevious());
    }
}

class FooException extends \Exception {}
