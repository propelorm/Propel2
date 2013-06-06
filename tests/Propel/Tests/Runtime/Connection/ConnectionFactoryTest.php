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

use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionWrapper;

use \PDO;

class ConnectionFactoryTest extends BaseTestCase
{
    /**
     * @expectedException \Propel\Runtime\Exception\InvalidArgumentException
     */
    public function testCreateFailsIfGivenIncorrectConfiguration()
    {
        $con = ConnectionFactory::create(array(), new SqliteAdapter());
    }

    public function testCreateReturnsAConnectionWrapperByDefault()
    {
        $con = ConnectionFactory::create(array('dsn' => 'sqlite::memory:'), new SqliteAdapter());
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionWrapper', $con);
    }

    public function testCreateReturnsACustomConnectionClassIfPassedAsThirdArgument()
    {
        $con = ConnectionFactory::create(array('dsn' => 'sqlite::memory:'), new SqliteAdapter(), 'Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest1');
        $this->assertInstanceOf('Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest1', $con);
    }

    public function testCreateReturnsACustomConnectionClassIfPassedInConfiguration()
    {
        $con = ConnectionFactory::create(array('dsn' => 'sqlite::memory:', 'classname' => 'Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest2'), new SqliteAdapter());
        $this->assertInstanceOf('Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest2', $con);
    }

    public function testCreatePreferablyUsesCustomConnectionClassFromConfiguration()
    {
        $con = ConnectionFactory::create(array('dsn' => 'sqlite::memory:', 'classname' => 'Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest2'), new SqliteAdapter(), 'Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest1');
        $this->assertInstanceOf('Propel\Tests\Runtime\Connection\MyConnectionForFactoryTest2', $con);
    }

    public function testCreateReturnsWrappedConnectionBuildByTheAdapter()
    {
        $con = ConnectionFactory::create(array('dsn' => 'sqlite::memory:'), new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertInstanceOf('Propel\Runtime\Connection\PdoConnection', $pdo);
    }

    public function testCreateSetsAttributesAfterConnection()
    {
        $con = ConnectionFactory::create(array('dsn' => 'sqlite::memory:', 'attributes' => array(PDO::ATTR_CASE => PDO::CASE_LOWER)), new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_LOWER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    public function testCreateSetsAttributesAfterConnectionAndExpandsConstantNames()
    {
        $con = ConnectionFactory::create(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CASE' => PDO::CASE_LOWER)), new SqliteAdapter());
        $pdo = $con->getWrappedConnection();
        $this->assertEquals(PDO::CASE_LOWER, $pdo->getAttribute(PDO::ATTR_CASE));
    }

    /**
     * @expectedException \Propel\Runtime\Exception\InvalidArgumentException
     */
    public function testCreateFailsWhenPassedAnIncorrectAttributeName()
    {
        $con = ConnectionFactory::create(array('dsn' => 'sqlite::memory:', 'attributes' => array('ATTR_CAE' => PDO::CASE_LOWER)), new SqliteAdapter());
    }

}

class MyConnectionForFactoryTest1 extends ConnectionWrapper {}
class MyConnectionForFactoryTest2 extends ConnectionWrapper {}
