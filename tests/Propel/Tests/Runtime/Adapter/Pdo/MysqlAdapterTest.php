<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the DbMySQL adapter
 *
 * @see BookstoreDataPopulator
 * @author William Durand
 */
class MysqlAdapterTest extends TestCaseFixtures
{
    /**
     * @return array
     */
    public static function getConParams()
    {
        return [
            [
                [
                    'dsn' => 'dsn=my_dsn',
                    'settings' => [
                        'charset' => 'foobar',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getConParams
     *
     * @param array $conparams
     *
     * @return void
     */
    public function testPrepareParamsThrowsException($conparams)
    {
        $db = new TestableMysqlAdapter();
        $result = $db->prepareParams($conparams);

        $this->assertIsArray($result);
    }

    /**
     * @dataProvider getConParams
     *
     * @return void
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
     *
     * @return void
     */
    public function testNoSetNameQueryExecuted($conparams)
    {
        $db = new TestableMysqlAdapter();
        $params = $db->prepareParams($conparams);

        $settings = [];
        if (isset($params['settings'])) {
            $settings = $params['settings'];
        }

        $db->initConnection($this->getPdoMock(), $settings);
    }

    protected function getPdoMock()
    {
        $con = $this
            ->getMockBuilder('\Propel\Runtime\Connection\ConnectionInterface')->getMock();

        $con
            ->expects($this->never())
            ->method('exec');

        return $con;
    }
}

class TestableMysqlAdapter extends MysqlAdapter
{
    /**
     * @param array $conparams
     *
     * @return array
     */
    public function prepareParams($conparams)
    {
        return parent::prepareParams($conparams);
    }
}
