<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Connection;

use Error;
use Exception;
use Propel\Tests\TestCase;
use Throwable;

/**
 * Tests the PdoConnection class
 *
 * @author Markus Staab <markus.staab@redaxo.de>
 */
class TransactionTraitTest extends TestCase
{
    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testTransactionRollback()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->once())->method('rollback');
        $con->expects($this->never())->method('commit');

        try {
            $con->transaction(function () {
                throw new Exception('boom');
            });
            $this->fail('missing exception');
        } catch (Exception $e) {
            $this->assertEquals('boom', $e->getMessage(), 'exception was rethrown');
        }
    }

    /**
     * @return void
     */
    public function testTransactionRollbackOnThrowable()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->once())->method('rollback');
        $con->expects($this->never())->method('commit');

        try {
            $con->transaction(function () {
                throw new Error('boom');
            });
            $this->fail('missing throwable');
        } catch (Throwable $e) {
            $this->assertEquals('boom', $e->getMessage(), 'exception was rethrown');
        }
    }

    /**
     * @return void
     */
    public function testTransactionCommit()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->never())->method('rollback');
        $con->expects($this->once())->method('commit');

        $this->assertNull($con->transaction(function () {
            // do nothing
        }), 'transaction() returns null by default');
    }

    public function testTransactionChaining()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->never())->method('rollback');
        $con->expects($this->once())->method('commit');

        $this->assertSame('myval', $con->transaction(function () {
            return 'myval';
        }), 'transaction() returns the returned value from the Closure');
    }

    /**
     * @return void
     */
    public function testTransactionNestedCommit()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->exactly(2))->method('beginTransaction');
        $con->expects($this->never())->method('rollback');
        $con->expects($this->exactly(2))->method('commit');

        $this->assertNull($con->transaction(function () use ($con) {
            $this->assertNull($con->transaction(function () {
                // do nothing
            }), 'transaction() returns null by default');
        }), 'transaction() returns null by default');
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testTransactionNestedException()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->exactly(2))->method('beginTransaction');
        $con->expects($this->exactly(2))->method('rollback');
        $con->expects($this->never())->method('commit');

        try {
            $con->transaction(function () use ($con) {
                $con->transaction(function () {
                    throw new Exception('boooom');
                });
            });
            $this->fail('expecting a nested exception to be re-thrown');
        } catch (Exception $e) {
            $this->assertEquals('boooom', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function testTransactionNestedThrowable()
    {
        $con = $this->getMockForTrait('Propel\Runtime\Connection\TransactionTrait');

        $con->expects($this->exactly(2))->method('beginTransaction');
        $con->expects($this->exactly(2))->method('rollback');
        $con->expects($this->never())->method('commit');

        try {
            $con->transaction(function () use ($con) {
                $con->transaction(function () {
                    throw new Error('boooom');
                });
            });
            $this->fail('expecting a nested throwable to be re-thrown');
        } catch (Throwable $e) {
            $this->assertEquals('boooom', $e->getMessage());
        }
    }
}
