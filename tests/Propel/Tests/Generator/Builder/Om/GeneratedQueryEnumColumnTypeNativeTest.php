<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Bookstore\Book2;
use Propel\Tests\Bookstore\Book2Query;
use Propel\Tests\Bookstore\Map\BookTableMap;

/**
 * Tests the generated queries for enum column types filters with a native implementation
 *
 */
class GeneratedQueryEnumColumnTypeNativeTest extends BookstoreTestBase
{
    public function setUp()
    {
        if ($this->runningOnSQLite()) {
            $this->markTestSkipped('SQLite does not support native Enum');
        }
        
        //parent::setUp();
    }

    public function testColumnHydration()
    {
        Book2Query::create()->deleteAll();

        $e0 = new Book2();
        $e0->save($this->con);
        $e = Book2Query::create()
            ->orderById()
            ->findOne($this->con);
        // empty check
        $this->assertEquals(null, $e->getStyle3(), 'enum null columns are correctly hydrated');
        // default value check
        $this->assertEquals('essay', $e->getStyle4(), 'enum default value columns are correctly hydrated');
        $e0->delete($this->con);

        $e1 = new Book2();
        $e1->setStyle3('novel');
        $e1->setStyle4('poetry');
        $e1->save($this->con);
        $e = Book2Query::create()
            ->orderById()
            ->findOne($this->con);
        // value check
        $this->assertEquals('novel', $e->getStyle3(), 'enum columns are correctly hydrated');
        $this->assertEquals('poetry', $e->getStyle4(), 'enum columns are correctly hydrated');
        $e1->delete($this->con);
    }

    public function testWhere()
    {
        Book2Query::create()->deleteAll();
        
        $e0 = new Book2();
        $e0->setStyle3('novel');
        $e0->setStyle4('poetry');
        $e0->save($this->con);
        $e1 = new Book2();
        $e1->setStyle3('novel');
        $e1->setStyle4('poetry');
        $e1->save($this->con);
        $e2 = new Book2();
        $e2->setStyle3('essay');
        $e2->setStyle4('novel');
        $e2->save($this->con);
        
        $e = Book2Query::create()
            ->where('Book2.Style3 = ?', 'novel')
            ->find($this->con);
        $this->assertEquals(2, $e->count(), 'object columns are searchable by enumerated value using where()');
        $this->assertEquals('novel', $e[0]->getStyle3(), 'object columns are searchable by enumerated value using where()');
        $e = Book2Query::create()
            ->where('Book2.Style4 IN ?', ['poetry', 'novel'])
            ->find($this->con);
        $this->assertEquals(3, $e->count(), 'object columns are searchable by enumerated value using where()');
        $e0->delete($this->con);
        $e1->delete($this->con);
        $e2->delete($this->con);
    }

    public function testFilterByColumn()
    {
        Book2Query::create()->deleteAll();

        $e0 = new Book2();
        $e0->setStyle3('novel');
        $e0->setStyle4('poetry');
        $e0->save($this->con);
        $e1 = new Book2();
        $e1->setStyle3('novel');
        $e1->setStyle4('poetry');
        $e1->save($this->con);
        $e2 = new Book2();
        $e2->setStyle3('essay');
        $e2->setStyle4('novel');
        $e2->save($this->con);
        
        $e = Book2Query::create()
            ->filterByStyle4('novel')
            ->findOne($this->con);
        $this->assertEquals('novel', $e->getStyle4(), 'enum columns are searchable by enumerated value');
        $e = Book2Query::create()
            ->filterByStyle4('poetry', Criteria::NOT_EQUAL)
            ->findOne($this->con);
        $this->assertEquals('novel', $e->getStyle4(), 'enum columns are searchable by enumerated value');
        $nb = Book2Query::create()
            ->filterByStyle3(['novel', 'essay'], Criteria::IN)
            ->count($this->con);
        $this->assertEquals(3, $nb, 'enum columns are searchable by enumerated value');
        $nb = Book2Query::create()
            ->filterByStyle4(['poetry', 'novel'])
            ->count($this->con);
        $this->assertEquals(3, $nb, 'enum columns filters default to Criteria IN when passed an array');

        $e0->delete($this->con);
        $e1->delete($this->con);
        $e2->delete($this->con);
    }
}
