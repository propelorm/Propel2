<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Connection;

use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Tests\Helpers\BaseTestCase;

class ConnectionManagerSingleTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testGetNameReturnsNameSetUsingSetName()
    {
        $manager = new ConnectionManagerSingle('foo');
        $this->assertSame('foo', $manager->getName());
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionFailsIfManagerIsNotConfigured()
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = new ConnectionManagerSingle('single');
        $con = $manager->getWriteConnection(new SqliteAdapter());
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionBuildsConnectionBasedOnConfiguration()
    {
        $manager = new ConnectionManagerSingle('single');
        $manager->setConfiguration(['dsn' => 'sqlite::memory:']);
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionReturnsAConnectionNamedAfterTheManager()
    {
        $manager = new ConnectionManagerSingle('foo');
        $manager->setConfiguration(['dsn' => 'sqlite::memory:']);
        $con = $manager->getWriteConnection(new SqliteAdapter());
        $this->assertEquals('foo', $con->getName());
    }

    /**
     * @return void
     */
    public function testGetReadConnectionReturnsWriteConnection()
    {
        $manager = new ConnectionManagerSingle('single');
        $manager->setConfiguration(['dsn' => 'sqlite::memory:']);
        $writeCon = $manager->getWriteConnection(new SqliteAdapter());
        $readCon = $manager->getReadConnection(new SqliteAdapter());
        $this->assertSame($writeCon, $readCon);
    }

    /**
     * @return void
     */
    public function testSetConnection()
    {
        $connection = new PdoConnection('sqlite::memory:');
        $manager = new ConnectionManagerSingle('single');
        $manager->setConnection($connection);
        $conn = $manager->getWriteConnection();
        $this->assertSame($connection, $conn);
    }
}
