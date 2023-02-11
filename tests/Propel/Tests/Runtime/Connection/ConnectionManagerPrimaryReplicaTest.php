<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Connection;

use PDO;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionManagerPrimaryReplica;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Tests\Helpers\BaseTestCase;

class ConnectionManagerPrimaryReplicaTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testGetNameReturnsNameSetUsingSetName()
    {
        $manager = new ConnectionManagerPrimaryReplica('foo');
        $this->assertSame('foo', $manager->getName());
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionFailsIfManagerIsNotConfigured()
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = new ConnectionManagerPrimaryReplica('default');
        $manager->getWriteConnection(new SqliteAdapter());
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionBuildsConnectionBasedOnWriteConfiguration()
    {
        $manager = new ConnectionManagerPrimaryReplica('default');
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
        $manager = new ConnectionManagerPrimaryReplica('default');
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
        $manager = new ConnectionManagerPrimaryReplica('foo');
        $manager->setWriteConfiguration(['dsn' => 'sqlite::memory:']);
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertEquals('foo', $con->getName());
    }

    /**
     * @return void
     */
    public function testGetReadConnectionBuildsConnectionBasedOnReadConfiguration()
    {
        $manager = new ConnectionManagerPrimaryReplica('default');
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
        $manager = new ConnectionManagerPrimaryReplica('default');
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
        $manager = new ConnectionManagerPrimaryReplica('default');
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
        $manager = new ConnectionManagerPrimaryReplica('default');
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
        $manager = new ConnectionManagerPrimaryReplica('foo');
        $manager->setReadConfiguration([['dsn' => 'sqlite::memory:']]);
        $con = $manager->getReadConnection(new SqliteAdapter());
        $this->assertEquals('foo', $con->getName());
    }

    /**
     * @return void
     */
    public function testIsForcePrimaryConnectionFalseByDefault()
    {
        $manager = new ConnectionManagerPrimaryReplica('default');
        $this->assertFalse($manager->isForcePrimaryConnection());
    }

    /**
     * @return void
     */
    public function testSetForcePrimaryConnection()
    {
        $manager = new ConnectionManagerPrimaryReplica('default');
        $manager->setForcePrimaryConnection(true);
        $this->assertTrue($manager->isForcePrimaryConnection());
        $manager->setForcePrimaryConnection(false);
        $this->assertFalse($manager->isForcePrimaryConnection());
    }

    /**
     * @return void
     */
    public function testForcePrimaryConnectionForcesMasterConnectionOnRead()
    {
        $manager = new ConnectionManagerPrimaryReplica('default');
        $manager->setForcePrimaryConnection(true);
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
        $manager = new ConnectionManagerPrimaryReplica('default');
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
