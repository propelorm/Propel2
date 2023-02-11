<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Connection;

use PDO;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionManagerMasterSlave;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * @deprecated Will be removed with the deprecated class.
 */
class ConnectionManagerMasterSlaveTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testGetNameReturnsNameSetUsingSetName()
    {
        $manager = new ConnectionManagerMasterSlave('foo');
        $this->assertSame('foo', $manager->getName());
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionFailsIfManagerIsNotConfigured()
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = new ConnectionManagerMasterSlave('default');
        $con = $manager->getWriteConnection(new SqliteAdapter());
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionBuildsConnectionBasedOnWriteConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setWriteConfiguration(['dsn' => 'sqlite::memory:']);
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionBuildsConnectionNotBasedOnReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setWriteConfiguration(['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_UPPER]]);
        $manager->setReadConfiguration([['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_LOWER]]]);
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_UPPER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionReturnsAConnectionNamedAfterTheManager()
    {
        $manager = new ConnectionManagerMasterSlave('foo');
        $manager->setWriteConfiguration(['dsn' => 'sqlite::memory:']);
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertEquals('foo', $con->getName());
    }

    /**
     * @return void
     */
    public function testGetReadConnectionBuildsConnectionBasedOnReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setReadConfiguration([['dsn' => 'sqlite::memory:']]);
        $con = $manager->getReadConnection(new SqliteAdapter());
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    /**
     * @return void
     */
    public function testGetReadConnectionBuildsConnectionNotBasedOnWriteConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setWriteConfiguration(['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_UPPER]]);
        $manager->setReadConfiguration([['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_LOWER]]]);
        $con = $manager->getReadConnection(new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_LOWER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    /**
     * @return void
     */
    public function testGetReadConnectionReturnsWriteConnectionIfNoReadConnectionIsSet()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setWriteConfiguration(['dsn' => 'sqlite::memory:']);
        $writeCon = $manager->getWriteConnection(new SqliteAdapter());
        $readCon = $manager->getReadConnection(new SqliteAdapter());
        $this->assertSame($writeCon, $readCon);
    }

    /**
     * @return void
     */
    public function testGetReadConnectionBuildsConnectionBasedOnARandomReadConfiguration()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setReadConfiguration([
            ['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_LOWER]],
            ['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_UPPER]],
        ]);
        $con = $manager->getReadConnection(new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $expected = [PDO::CASE_LOWER, PDO::CASE_UPPER];
        $this->assertContains($pdo->getAttribute(PDO::ATTR_CASE), $expected);
    }

    /**
     * @return void
     */
    public function testGetReadConnectionReturnsAConnectionNamedAfterTheManager()
    {
        $manager = new ConnectionManagerMasterSlave('foo');
        $manager->setReadConfiguration([['dsn' => 'sqlite::memory:']]);
        $con = $manager->getReadConnection(new SqliteAdapter());
        $this->assertEquals('foo', $con->getName());
    }

    /**
     * @return void
     */
    public function testIsForceMasterConnectionFalseByDefault()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $this->assertFalse($manager->isForceMasterConnection());
    }

    /**
     * @return void
     */
    public function testSetForceMasterConnection()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setForceMasterConnection(true);
        $this->assertTrue($manager->isForceMasterConnection());
        $manager->setForceMasterConnection(false);
        $this->assertFalse($manager->isForceMasterConnection());
    }

    /**
     * @return void
     */
    public function testForceMasterConnectionForcesMasterConnectionOnRead()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setForceMasterConnection(true);
        $manager->setWriteConfiguration(['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_UPPER]]);
        $manager->setReadConfiguration([['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_LOWER]]]);
        $con = $manager->getReadConnection(new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_UPPER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    /**
     * When master is in transaction then we need to return the master connection for getReadConnection,
     * otherwise lookup queries fail
     *
     * @return void
     */
    public function testReadConnectionWhenMasterIsInTransaction()
    {
        $manager = new ConnectionManagerMasterSlave('default');
        $manager->setWriteConfiguration(['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_UPPER]]);
        $manager->setReadConfiguration([['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_LOWER]]]);

        $writeConnection = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertFalse($writeConnection->inTransaction());

        $this->assertNotSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));
        $writeConnection->beginTransaction();
        $this->assertSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));
        $writeConnection->rollBack();
        $this->assertNotSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));

        $writeConnection->beginTransaction();
        $this->assertSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));
        $writeConnection->commit();
        $this->assertNotSame($writeConnection, $manager->getReadConnection(new SqliteAdapter()));
    }
}
