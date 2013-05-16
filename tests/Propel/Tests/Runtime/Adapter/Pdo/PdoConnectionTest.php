<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Runtime\Propel;
use Propel\Runtime\Adapter\Pdo\OracleAdapter;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Util\BasePeer;
use Propel\Runtime\Adapter\Pdo\PdoConnection;

/**
 * Tests the PdoConnection class
 * 
 * @author Markus Staab <maggus.staab@googlemail.com>
 */
class PdoConnectionTest extends \PHPUnit_Framework_TestCase {
    
    public function testTransactionRollback() {
        $con = $this->getMock('PdoConnection', [], ['sqlite::memory:']);
        
        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->once())->method('rollback');
        $con->expects($this->never())->method('commit');

        $con->transaction(function() {
            throw new \Exception("should trigger a rollback");
        });
    }
    
    public function testTransactionCommit() {
        $con = $this->getMock('PdoConnection', [], ['sqlite::memory:']);
        
        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->never())->method('rollback');
        $con->expects($this->once())->method('commit');

        $this->assertTrue($con->transaction(function() {
            // do nothing
        }), "transaction() returns true by default");
    }
    
    public function testTransactionChaining() {
        $con = $this->getMock('PdoConnection', [], ['sqlite::memory:']);
        
        $con->expects($this->once())->method('beginTransaction');
        $con->expects($this->never())->method('rollback');
        $con->expects($this->once())->method('commit');

        $this->assertSame("myval", $con->transaction(function() {
            return "myval";
        }), "transaction() returns the returned value from the Closure");
    }
}