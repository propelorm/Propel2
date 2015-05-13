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
        $manager = new ConnectionManagerMasterSlave();
        $this->assertNull($manager->getName());
    }

    public function testGetNameReturnsNameSetUsingSetName()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setName('foo');
        $this->assertEquals('foo', $manager->getName());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\InvalidArgumentException
     */
    public function testGetWriteConnectionFailsIfManagerIsNotConfigured()
    {
        $manager = new ConnectionManagerMasterSlave();
        $con = $manager->getWriteConnection(new SqliteAdapter());
    }

    public function testGetWriteConnectionBuildsConnectionBasedOnWriteConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:'));
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    public function testGetWriteConnectionBuildsConnectionNotBasedOnReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER)));
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER))));
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_UPPER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    public function testGetWriteConnectionReturnsAConnectionNamedAfterTheManager()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setName('foo');
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:'));
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertEquals('foo', $con->getName());
    }

    public function testGetReadConnectionBuildsConnectionBasedOnReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:')));
        $con = $manager->getReadConnection(new SqliteAdapter());
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    public function testGetReadConnectionBuildsConnectionNotBasedOnWriteConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER)));
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER))));
        $con = $manager->getReadConnection(new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_LOWER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    public function testGetReadConnectionReturnsWriteConnectionIfNoReadConnectionIsSet()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:'));
        $writeCon = $manager->getWriteConnection(new SqliteAdapter());
        $readCon  = $manager->getReadConnection(new SqliteAdapter());
        $this->assertSame($writeCon, $readCon);
    }

    public function testGetReadConnectionBuildsConnectionBasedOnARandomReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setReadConfiguration(array(
            array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER)),
            array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER))
        ));
        $con = $manager->getReadConnection(new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $expected = array(PDO::CASE_LOWER, PDO::CASE_UPPER);
        $this->assertContains($pdo->getAttribute(PDO::ATTR_CASE), $expected);
    }

    public function testGetReadConnectionReturnsAConnectionNamedAfterTheManager()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setName('foo');
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:')));
        $con = $manager->getReadConnection(new SqliteAdapter());
        $this->assertEquals('foo', $con->getName());
    }

    public function testIsForceMasterConnectionFalseByDefault()
    {
        $manager = new ConnectionManagerMasterSlave();
        $this->assertFalse($manager->isForceMasterConnection());
    }

    public function testSetForceMasterConnection()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setForceMasterConnection(true);
        $this->assertTrue($manager->isForceMasterConnection());
        $manager->setForceMasterConnection(false);
        $this->assertFalse($manager->isForceMasterConnection());
    }

    public function testForceMasterConnectionForcesMasterConnectionOnRead()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setForceMasterConnection(true);
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER)));
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER))));
        $con = $manager->getReadConnection(new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_UPPER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    /**
     * When master is in transaction then we need to return the master connection for getReadConnection,
     * otherwise lookup queries fail
     */
    public function testReadConnectionWhenMasterIsInTransaction()
    {
        $manager = new ConnectionManagerMasterSlave();
        $manager->setWriteConfiguration(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_UPPER)));
        $manager->setReadConfiguration(array(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER))));

        $writeConnection = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertFalse($writeConnection->inTransaction());

        $this->assertNotSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));
        $writeConnection->beginTransaction();
        $this->assertSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));
        $writeConnection->rollBack();

        $writeConnection->beginTransaction();
        $this->assertSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));
        $writeConnection->commit();
        $this->assertNotSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));
    }
}
