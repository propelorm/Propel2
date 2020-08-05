<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Connection;

use PDO;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Tests\Helpers\BaseTestCase;

class ConnectionFactoryTest extends BaseTestCase
{
    /**
     * @expectedException \Propel\Runtime\Exception\InvalidArgumentException
     *
     * @return void
     */
    public function testCreateFailsIfGivenIncorrectConfiguration()
    {
        $con = ConnectionFactory::create([], new SqliteAdapter());
    }

    /**
     * @return void
     */
    public function testCreateReturnsAConnectionWrapperByDefault()
    {
        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:'], new SqliteAdapter());
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
    }

    /**
     * @return void
     */
    public function testCreateReturnsACustomConnectionClassIfPassedAsThirdArgument()
    {
        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:'], new SqliteAdapter(), 'Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest1');
        $this->assertInstanceOf('Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest1', $con);
    }

    /**
     * @return void
     */
    public function testCreateReturnsACustomConnectionClassIfPassedInConfiguration()
    {
        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:', 'classname' => 'Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest2'], new SqliteAdapter());
        $this->assertInstanceOf('Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest2', $con);
    }

    /**
     * @return void
     */
    public function testCreatePreferablyUsesCustomConnectionClassFromConfiguration()
    {
        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:', 'classname' => 'Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest2'], new SqliteAdapter(), 'Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest1');
        $this->assertInstanceOf('Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest2', $con);
    }

    /**
     * @return void
     */
    public function testCreateReturnsWrappedConnectionBuildByTheAdapter()
    {
        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:'], new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    /**
     * @return void
     */
    public function testCreateSetsAttributesAfterConnection()
    {
        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:', 'attributes' => [PDO::ATTR_CASE => PDO::CASE_LOWER]], new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_LOWER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    /**
     * @return void
     */
    public function testCreateSetsAttributesAfterConnectionAndExpandsConstantNames()
    {
        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CASE' => PDO::CASE_LOWER]], new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_LOWER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    /**
     * @expectedException \Propel\Runtime\Exception\InvalidArgumentException
     *
     * @return void
     */
    public function testCreateFailsWhenPassedAnIncorrectAttributeName()
    {
        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CAE' => PDO::CASE_LOWER]], new SqliteAdapter());
    }
}

class MyConnectionForFactoryTest1 extends ConnectionWrapper
{
}
class MyConnectionForFactoryTest2 extends ConnectionWrapper
{
}
