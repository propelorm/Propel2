<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Connection;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\Connection\ConnectionManagerMasterSlave;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;

use \PDO;

class ConnectionManagerMasterSlaveTest extends BaseTestCase
{
    public function testGetNameReturnsNullByDefault()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $this->assertNull($manager->getName());
    }

    public function testGetNameReturnsNameSetUsingSetName()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $manager->setName('foo');
        $this->assertEquals('foo', $manager->getName());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\InvalidArgumentException
     */
    public function testGetWriteConnectionFailsIfManagerIsNotConfigured()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $con = $manager->getWriteConnection();
    }

    public function testGetWriteConnectionBuildsConnectionBasedOnWriteConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:'));
        $con = $manager->getWriteConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    public function testGetWriteConnectionBuildsConnectionNotBasedOnReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER)));
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER))));
        $con = $manager->getWriteConnection();
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_UPPER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    public function testGetWriteConnectionReturnsAConnectionNamedAfterTheManager()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter);
        $manager->setName('foo');
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:'));
        $con = $manager->getWriteConnection();
        $this->assertEquals('foo', $con->getName());
    }

    public function testGetReadConnectionBuildsConnectionBasedOnReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter);
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:')));
        $con = $manager->getReadConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    public function testGetReadConnectionBuildsConnectionNotBasedOnWriteConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter);
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER)));
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER))));
        $con = $manager->getReadConnection();
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_LOWER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    public function testGetReadConnectionReturnsWriteConnectionIfNoReadConnectionIsSet()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:'));
        $writeCon = $manager->getWriteConnection();
        $readCon  = $manager->getReadConnection();
        $this->assertSame($writeCon, $readCon);
    }

    public function testGetReadConnectionBuildsConnectionBasedOnARandomReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $manager->setReadConfiguration(array(
            array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER)),
            array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER))
        ));
        $con = $manager->getReadConnection();
        $pdo = $con->getWrappedConnection();
        $expected = array(PDO::CASE_LOWER, PDO::CASE_UPPER);
        $this->assertContains($pdo->getAttribute(PDO::ATTR_CASE), $expected);
    }

    public function testGetReadConnectionReturnsAConnectionNamedAfterTheManager()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $manager->setName('foo');
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:')));
        $con = $manager->getReadConnection();
        $this->assertEquals('foo', $con->getName());
    }

    public function testIsForceMasterConnectionFalseByDefault()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $this->assertFalse($manager->isForceMasterConnection());
    }

    public function testSetForceMasterConnection()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $manager->setForceMasterConnection(true);
        $this->assertTrue($manager->isForceMasterConnection());
        $manager->setForceMasterConnection(false);
        $this->assertFalse($manager->isForceMasterConnection());
    }

    public function testForceMasterConnectionForcesMasterConnectionOnRead()
    {
        $manager = new ConnectionManagerMasterSlave(new SqliteAdapter());
        $manager->setForceMasterConnection(true);
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER)));
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER))));
        $con = $manager->getReadConnection();
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_UPPER, $pdo->getAttribute(PDO::ATTR_CASE));
    }
}
