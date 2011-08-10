<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../../../tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests the DbOracle adapter
 *
 * @see        BookstoreDataPopulator
 * @author     William Durand
 * @package    runtime.adapter
 */
class DBMySQLTest extends BookstoreTestBase
{
	public function testPrepareParams()
	{
		$db = new DBMySQL();
		$conparams = array(
			'dsn' => 'dsn=my_dsn',
			'settings' => array(
				'charset' => array(
					'value' => 'foobar'
				)
			)
		);

		$params = array();

		try {
			$params = $db->prepareParams($conparams);

			if(version_compare(PHP_VERSION, '5.3.6', '<')) {
				$this->fail();
			}
		} catch (Exception $e) {
			$this->assertTrue(true, 'Exception catched');
		}

		if(version_compare(PHP_VERSION, '5.3.6', '>=')) {
			$this->assertTrue(is_array($params));
			$this->assertEquals('dsn=my_dsn;charset=foobar', $params['dsn'], 'The given charset is in the DSN string');
		}
	}
}
