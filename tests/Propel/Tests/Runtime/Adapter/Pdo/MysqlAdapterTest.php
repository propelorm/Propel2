<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the DbMySQL adapter
 *
 * @see        BookstoreDataPopulator
 * @author William Durand
 */
class MysqlAdapterTest extends TestCaseFixtures
{
    public static function getConParams()
    {
        return array(
            array(
                array(
                    'dsn' => 'dsn=my_dsn',
                    'settings' => array(
                        'charset' => 'foobar'
                    )
                )
            )
        );
    }

    /**
     * @dataProvider getConParams
     */
    public function testPrepareParamsThrowsException($conparams)
    {
        $db = new TestableMysqlAdapter();
        $db->prepareParams($conparams);
    }

    /**
     * @dataProvider getConParams
     */
    public function testPrepareParams($conparams)
    {
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
            ->getMock('\Propel\Runtime\Connection\ConnectionInterface');

        $con
            ->expects($this->never())
            ->method('exec');

        return $con;
    }
}

class TestableMysqlAdapter extends MysqlAdapter
{
    public function prepareParams($conparams)
    {
        return parent::prepareParams($conparams);
    }
}
