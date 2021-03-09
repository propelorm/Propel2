<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\BookstoreCashier;
use Propel\Tests\Bookstore\BookstoreCashierQuery;
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery;
use Propel\Tests\Bookstore\BookstoreManager;
use Propel\Tests\Bookstore\BookstoreManagerQuery;
use Propel\Tests\Bookstore\DistributionManager;
use Propel\Tests\Bookstore\DistributionQuery;
use Propel\Tests\Bookstore\DistributionStore;
use Propel\Tests\Bookstore\DistributionVirtualStore;
use Propel\Tests\Bookstore\Map\DistributionTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Test class for MultiExtensionQueryBuilder.
 *
 * @author François Zaninotto
 *
 * @group database
 */
class QueryBuilderInheritanceTest extends BookstoreTestBase
{
    public function constructProvider()
    {
        return [
            ['BookstoreCashierQuery'],
            ['BookstoreEmployeeQuery'],
            ['BookstoreManagerQuery'],
            ['BookstoreHeadQuery'],
            ['DistributionStoreQuery'],
            ['DistributionOnlineQuery'],
            ['DistributionVirtualStoreQuery'],
        ];
    }

    /**
     * @dataProvider constructProvider
     *
     * @return void
     */
    public function testConstruct($class)
    {
        $class = 'Propel\\Tests\\Bookstore\\' . $class;
        $query = $class::create();
        $this->assertTrue($query instanceof $class, 'the create() factory returns an instance of the correct class');
    }

    /**
     * @return void
     */
    public function testFindFilter()
    {
        BookstoreDataPopulator::depopulate($this->con);
        $employee = new BookstoreEmployee();
        $employee->save($this->con);
        $manager = new BookstoreManager();
        $manager->save($this->con);
        $cashier1 = new BookstoreCashier();
        $cashier1->save($this->con);
        $cashier2 = new BookstoreCashier();
        $cashier2->save($this->con);
        $nbEmp = BookstoreEmployeeQuery::create()->count($this->con);
        $this->assertEquals(4, $nbEmp, 'find() in main query returns all results');
        $nbMan = BookstoreManagerQuery::create()->count($this->con);
        $this->assertEquals(1, $nbMan, 'find() in sub query returns only child results');
        $nbCash = BookstoreCashierQuery::create()->count($this->con);
        $this->assertEquals(2, $nbCash, 'find() in sub query returns only child results');
    }

    /**
     * @return void
     */
    public function testUpdateFilter()
    {
        BookstoreDataPopulator::depopulate($this->con);
        $manager = new BookstoreManager();
        $manager->save($this->con);
        $cashier1 = new BookstoreCashier();
        $cashier1->save($this->con);
        $cashier2 = new BookstoreCashier();
        $cashier2->save($this->con);
        BookstoreManagerQuery::create()->update(['Name' => 'foo'], $this->con);
        $nbMan = BookstoreEmployeeQuery::create()
            ->filterByName('foo')
            ->count($this->con);
        $this->assertEquals(1, $nbMan, 'Update in sub query affects only child results');
    }

    /**
     * @return void
     */
    public function testDeleteFilter()
    {
        BookstoreDataPopulator::depopulate($this->con);
        $manager = new BookstoreManager();
        $manager->save($this->con);
        $cashier1 = new BookstoreCashier();
        $cashier1->save($this->con);
        $cashier2 = new BookstoreCashier();
        $cashier2->save($this->con);
        BookstoreManagerQuery::create()
            ->filterByName()
            ->delete();
        $nbCash = BookstoreEmployeeQuery::create()->count();
        $this->assertEquals(2, $nbCash, 'Delete in sub query affects only child results');
    }

    /**
     * @return void
     */
    public function testDeleteAllFilter()
    {
        BookstoreDataPopulator::depopulate($this->con);
        $manager = new BookstoreManager();
        $manager->save($this->con);
        $cashier1 = new BookstoreCashier();
        $cashier1->save($this->con);
        $cashier2 = new BookstoreCashier();
        $cashier2->save($this->con);
        BookstoreManagerQuery::create()->deleteAll();
        $nbCash = BookstoreEmployeeQuery::create()->count();
        $this->assertEquals(2, $nbCash, 'Delete in sub query affects only child results');
    }

    /**
     * @return void
     */
    public function testFindPkSimpleWithSingleTableInheritanceReturnCorrectClass()
    {
        Propel::disableInstancePooling();

        $employee = new BookstoreEmployee();
        $employee->save($this->con);
        $manager = new BookstoreManager();
        $manager->save($this->con);
        $cashier1 = new BookstoreCashier();
        $cashier1->save($this->con);
        $cashier2 = new BookstoreCashier();
        $cashier2->save($this->con);

        $this->assertInstanceOf(
            '\Propel\Tests\Bookstore\BookstoreEmployee',
            BookstoreEmployeeQuery::create()->findPk($employee->getId()),
            'findPk() return right object : BookstoreEmployee'
        );
        $this->assertInstanceOf(
            '\Propel\Tests\Bookstore\BookstoreManager',
            BookstoreEmployeeQuery::create()->findPk($manager->getId()),
            'findPk() return right object : BookstoreManager'
        );
        $this->assertInstanceOf(
            '\Propel\Tests\Bookstore\BookstoreCashier',
            BookstoreEmployeeQuery::create()->findPk($cashier1->getId()),
            'findPk() return right object : BookstoreCashier'
        );
        $this->assertInstanceOf(
            '\Propel\Tests\Bookstore\BookstoreCashier',
            BookstoreEmployeeQuery::create()->findPk($cashier2->getId()),
            'findPk() return right object : BookstoreCashier'
        );

        Propel::enableInstancePooling();
    }

    /**
     * @return void
     */
    public function testGetCorrectTableMapClassWithAbstractSingleTableInheritance()
    {
        $this->assertInstanceOf('\Propel\Tests\Bookstore\Map\DistributionTableMap', DistributionTableMap::getTableMap(), 'getTableMap should return the right table map');
    }

    /**
     * This test prove failure with propel.emulateForeignKeyConstraints = true
     *
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testDeleteCascadeWithAbstractSingleTableInheritance()
    {
        $manager = new DistributionManager();
        $manager->setName('test');
        $manager->save();
        $manager->delete();
    }

    /**
     * @return void
     */
    public function testFindPkSimpleWithAbstractSingleTableInheritanceReturnCorrectClass()
    {
        Propel::disableInstancePooling();

        $manager = new DistributionManager();
        $manager->setName('manager1');
        $manager->save();

        $distributionStore = new DistributionStore();
        $distributionStore->setName('my store 1');
        $distributionStore->setDistributionManager($manager);
        $distributionStore->save();

        $distributionVirtualStore = new DistributionVirtualStore();
        $distributionVirtualStore->setName('my VirtualStore 1');
        $distributionVirtualStore->setDistributionManager($manager);
        $distributionVirtualStore->save();

        $this->assertInstanceOf(
            'Propel\Tests\Bookstore\DistributionStore',
            DistributionQuery::create()->findPk($distributionStore->getId()),
            'findPk() return right object : DistributionStore'
        );
        $this->assertInstanceOf(
            'Propel\Tests\Bookstore\DistributionVirtualStore',
            DistributionQuery::create()->findPk($distributionVirtualStore->getId()),
            'findPk() return right object : DistributionVirtualStore'
        );

        Propel::enableInstancePooling();
    }
}
