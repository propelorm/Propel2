<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Connection;

use Propel\Tests\TestCase;

/**
 * Tests the PdoConnection class
 *
 * @author Markus Staab <markus.staab@redaxo.de>
 */
class TransactionTraitTest extends TestCase
{
    public function testTransactionRollback()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->once())->method('rollback');
        $con->expects($this->never())->method('commit');

        try {
            $con->transaction(function() {
                throw new \Exception("boom");
            });
            $this->fail('missing exception');
        } catch (\Exception $e) {
            $this->assertEquals("boom", $e->getMessage(), "exception was rethrown");
        }
    }

    public function testTransactionCommit()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->never())->method('rollback');
        $con->expects($this->once())->method('commit');

        $this->assertNull($con->transaction(function() {
            // do nothing
        }), "transaction() returns null by default");
    }

    public function testTransactionChaining()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->never())->method('rollback');
        $con->expects($this->once())->method('commit');

        $this->assertSame("myval", $con->transaction(function() {
            return "myval";
        }), "transaction() returns the returned value from the Closure");
    }

    public function testTransactionNestedCommit()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->exactly(2))->method('beginTransaction');
        $con->expects($this->never())->method('rollback');
        $con->expects($this->exactly(2))->method('commit');

        $this->assertNull($con->transaction(function() use ($con) {
            $this->assertNull($con->transaction(function() {
                // do nothing
            }), "transaction() returns null by default");
        }), "transaction() returns null by default");
    }

    public function testTransactionNestedException()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->exactly(2))->method('beginTransaction');
        $con->expects($this->exactly(2))->method('rollback');
        $con->expects($this->never())->method('commit');

        try {
            $con->transaction(function() use ($con) {
                $con->transaction(function() {
                   throw new \Exception("boooom");
                });
            });
            $this->fail("expecting a nested exception to be re-thrown");
        } catch (\Exception $e) {
            $this->assertEquals("boooom", $e->getMessage());
        }
    }
}
