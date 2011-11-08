<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\adapter;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Runtime\Adapter\MysqlAdapter;

/**
 * Tests the DbMySQL adapter
 *
 * @see        BookstoreDataPopulator
 * @author     William Durand
 * @package    runtime.adapter
 */
class MysqlAdapterTest extends BookstoreTestBase
{
    public static function getConParams()
    {
        return array(
            array(
                array(
                    'dsn' => 'dsn=my_dsn',
                    'settings' => array(
                        'charset' => array(
                            'value' => 'foobar'
                        )
                    )
                )
            )
        );
    }

    /**
     * @dataProvider getConParams
     * @expectedException PropelException
     */
    public function testPrepareParamsThrowsException($conparams)
    {
        if (version_compare(PHP_VERSION, '5.3.6', '>=')) {
            $this->markTestSkipped('PHP_VERSION >= 5.3.6, no need to throw an exception.');
        }

        $db = new TestableMysqlAdapter();
        $db->prepareParams($conparams);
    }

    /**
     * @dataProvider getConParams
     */
    public function testPrepareParams($conparams)
    {
        if (version_compare(PHP_VERSION, '5.3.6', '<')) {
            $this->markTestSkipped('PHP_VERSION < 5.3.6 will throw an exception.');
        }

        $db = new TestableMysqlAdapter();
        $params = $db->prepareParams($conparams);

        $this->assertTrue(is_array($params));
        $this->assertEquals('dsn=my_dsn;charset=foobar', $params['dsn'], 'The given charset is in the DSN string');
        $this->assertArrayNotHasKey('charset', $params['settings'], 'The charset should be removed');
    }

    /**
     * @dataProvider getConParams
     */
    public function testNoSetNameQueryExecuted($conparams)
    {
        if (version_compare(PHP_VERSION, '5.3.6', '<')) {
            $this->markTestSkipped('PHP_VERSION < 5.3.6 will throw an exception.');
        }

        $db = new TestableMysqlAdapter();
        $params = $db->prepareParams($conparams);

        $settings = array();
        if (isset($params['settings'])) {
            $settings = $params['settings'];
        }

        $db->initConnection($this->getPdoMock(), $settings);
    }

    protected function getPdoMock()
    {
        $con = $this
            ->getMockBuilder('\Propel\Tests\Runtime\Adapter\MockPDO')
            ->getMock();

        $con
            ->expects($this->never())
            ->method('exec');

        return $con;
    }
}

// See: http://stackoverflow.com/questions/3138946/mocking-the-pdo-object-using-phpunit
class MockPDO extends \PDO
{
    public function __construct()
    {
    }
}

class TestableMysqlAdapter extends MysqlAdapter
{
    public function prepareParams($conparams)
    {
        return parent::prepareParams($conparams);
    }
}
