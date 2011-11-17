<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookPeer;
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeePeer;
use Propel\Tests\Bookstore\BookstoreCashier;
use Propel\Tests\Bookstore\BookstoreManager;

use Propel\Runtime\Configuration;
use Propel\Runtime\Formatter\ObjectFormatter;
use Propel\Runtime\Query\ModelCriteria;

/**
 * Test class for ObjectFormatter.
 *
 * @author     Francois Zaninotto
 * @version    $Id: ObjectFormatterTest.php 1374 2009-12-26 23:21:37Z francois $
 * @package    runtime.formatter
 */
class ObjectFormatterInheritanceTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        $b1 = new BookstoreEmployee();
        $b1->setName('b1');
        $b1->save();
        $b2 = new BookstoreManager();
        $b2->setName('b2');
        $b2->save();
        $b3 = new BookstoreCashier();
        $b3->setName('b3');
        $b3->save();
    }

    public function testFormat()
    {
        $con = Configuration::getInstance()->getConnection(BookPeer::DATABASE_NAME);
        BookstoreEmployeePeer::clearInstancePool();

        $stmt = $con->query('SELECT * FROM bookstore_employee');
        $formatter = new ObjectFormatter();
        $formatter->init(new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee'));
        $emps = $formatter->format($stmt);
        $expectedClass = array(
            'b1' =>'Propel\Tests\Bookstore\BookstoreEmployee',
            'b2' =>'Propel\Tests\Bookstore\BookstoreManager',
            'b3' =>'Propel\Tests\Bookstore\BookstoreCashier'
        );
        foreach ($emps as $emp) {
            $this->assertEquals($expectedClass[$emp->getName()], get_class($emp), 'format() creates objects of the correct class when using inheritance');
        }
    }

}
