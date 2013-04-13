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

use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;

class ConnectionManagerSingleTest extends BaseTestCase
{
    public function testGetNameReturnsNullByDefault()
    {
        $manager = new ConnectionManagerSingle();
        $this->assertNull($manager->getName());
    }

    public function testGetNameReturnsNameSetUsingSetName()
    {
        $manager = new ConnectionManagerSingle();
        $manager->setName('foo');
        $this->assertEquals('foo', $manager->getName());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\InvalidArgumentException
     */
    public function testGetWriteConnectionFailsIfManagerIsNotConfigured()
    {
        $manager = new ConnectionManagerSingle();
        $con = $manager->getWriteConnection(new SqliteAdapter());
    }

    public function testGetWriteConnectionBuildsConnectionBasedOnConfiguration()
    {
        $manager = new ConnectionManagerSingle();
        $manager->setConfiguration(array('dsn' => 'sqlite::memory:'));
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    public function testGetWriteConnectionReturnsAConnectionNamedAfterTheManager()
    {
        $manager = new ConnectionManagerSingle();
        $manager->setName('foo');
        $manager->setConfiguration(array('dsn' => 'sqlite::memory:'));
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertEquals('foo', $con->getName());
    }

    public function testGetReadConnectionReturnsWriteConnection()
    {
        $manager = new ConnectionManagerSingle();
        $manager->setConfiguration(array('dsn' => 'sqlite::memory:'));
        $writeCon = $manager->getWriteConnection(new SqliteAdapter());
        $readCon  = $manager->getReadConnection(new SqliteAdapter());
        $this->assertSame($writeCon, $readCon);
    }

    public function testSetConnection()
    {
        $connection = new PdoConnection('sqlite::memory:');
        $manager = new ConnectionManagerSingle();
        $manager->setConnection($connection);
        $conn = $manager->getWriteConnection();
        $this->assertSame($connection, $conn);
    }

}
