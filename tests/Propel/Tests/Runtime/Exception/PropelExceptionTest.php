<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Exception;

use Exception;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\TestCase;

/**
 * Test for PropelException class
 *
 * @author Francois Zaninotto
 */
class PropelExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testSimpleConstructor()
    {
        $e = new PropelException('this is an error');
        $this->assertTrue($e instanceof Exception);
        $this->assertEquals('this is an error', $e->getMessage());
    }

    /**
     * @return void
     */
    public function testCompositeConstructor()
    {
        $e1 = new FooException('real cause');
        $e = new PropelException('this is an error', 0, $e1);
        $this->assertEquals('this is an error', $e->getMessage());
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return void
     */
    public function testIsThrowable()
    {
        $this->expectException(PropelException::class);

        $e = new PropelException('this is an error');

        throw $e;
    }

    /**
     * @return void
     */
    public function testGetPrevious()
    {
        $e1 = new FooException('real cause');
        $e = new PropelException('this is an error', 0, $e1);
        $this->assertEquals($e1, $e->getPrevious());
    }
}

class FooException extends Exception
{
}
