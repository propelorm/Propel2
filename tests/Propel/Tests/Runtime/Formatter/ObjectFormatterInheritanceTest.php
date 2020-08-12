<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Formatter\ObjectFormatter;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\BookstoreCashier;
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreManager;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Test class for ObjectFormatter.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ObjectFormatterInheritanceTest extends BookstoreEmptyTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
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

    /**
     * @return void
     */
    public function testFormat()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        BookstoreEmployeeTableMap::clearInstancePool();

        $stmt = $con->query('SELECT id, class_key, name, job_title, supervisor_id, photo FROM bookstore_employee');
        $formatter = new ObjectFormatter();
        $formatter->init(new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee'));
        $emps = $formatter->format($stmt);
        $expectedClass = [
            'b1' => 'Propel\Tests\Bookstore\BookstoreEmployee',
            'b2' => 'Propel\Tests\Bookstore\BookstoreManager',
            'b3' => 'Propel\Tests\Bookstore\BookstoreCashier',
        ];
        foreach ($emps as $emp) {
            $this->assertEquals($expectedClass[$emp->getName()], get_class($emp), 'format() creates objects of the correct class when using inheritance');
        }
    }
}
