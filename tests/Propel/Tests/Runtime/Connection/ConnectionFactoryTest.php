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
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Tests\Helpers\BaseTestCase;
use Propel\Runtime\Connection\ProfilerConnectionWrapper;

class ConnectionFactoryTest extends BaseTestCase
{
    public function tearDown(): void
    {
        ConnectionFactory::$useProfilerConnection = false;
        parent::tearDown();
    }
    /**
     * @return void
     */
    public function testCreateFailsIfGivenIncorrectConfiguration()
    {
        $this->expectException(InvalidArgumentException::class);

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
     * @return void
     */
    public function testCreateFailsWhenPassedAnIncorrectAttributeName()
    {
        $this->expectException(InvalidArgumentException::class);

        $con = ConnectionFactory::create(['dsn' => 'sqlite::memory:', 'attributes' => ['ATTR_CAE' => PDO::CASE_LOWER]], new SqliteAdapter());
    }
    
    /**
     * @return void
     */
    public function testUseProfilerConnectionOverridesConnectionClass()
    {
        ConnectionFactory::$useProfilerConnection = true;
        $config = ['dsn' => 'sqlite::memory:', 'classname' => ConnectionWrapper::class];
        $con = ConnectionFactory::create($config, new SqliteAdapter());
        $this->assertInstanceOf(ProfilerConnectionWrapper::class, $con);
    }
}

class MyConnectionForFactoryTest1 extends ConnectionWrapper
{
}
class MyConnectionForFactoryTest2 extends ConnectionWrapper
{
}
