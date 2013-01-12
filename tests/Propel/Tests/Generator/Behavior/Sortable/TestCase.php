<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase as BaseTestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCase extends BaseTestCase
{
    public function setUp()
    {
        if (!class_exists('SortableTable11')) {
            $schema = <<<XML
<database name="bookstore-behavior" defaultIdMethod="native">
    <table name="sortable_table11">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />

        <behavior name="sortable" />
    </table>

    <table name="sortable_table12">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
        <column name="position" type="INTEGER" />

        <behavior name="sortable">
            <parameter name="rank_column" value="position" />
            <parameter name="use_scope" value="true" />
            <parameter name="scope_column" value="my_scope_column" />
        </behavior>
    </table>
</database>
XML;
            QuickBuilder::buildSchema($schema);
        }
    }

    protected function populateTable11()
    {
        \SortableTable11Peer::doDeleteAll();

        $t1 = new \SortableTable11();
        $t1->setRank(1);
        $t1->setTitle('row1');
        $t1->save();

        $t2 = new \SortableTable11();
        $t2->setRank(4);
        $t2->setTitle('row4');
        $t2->save();

        $t3 = new \SortableTable11();
        $t3->setRank(2);
        $t3->setTitle('row2');
        $t3->save();

        $t4 = new \SortableTable11();
        $t4->setRank(3);
        $t4->setTitle('row3');
        $t4->save();
    }

    protected function populateTable12()
    {
        /* List used for tests
         scope=1   scope=2
         row1      row5
         row2      row6
         row3
         row4
         */
        \SortableTable12Peer::doDeleteAll();

        $t1 = new \SortableTable12();
        $t1->setRank(1);
        $t1->setScopeValue(1);
        $t1->setTitle('row1');
        $t1->save();

        $t2 = new \SortableTable12();
        $t2->setRank(4);
        $t2->setScopeValue(1);
        $t2->setTitle('row4');
        $t2->save();

        $t3 = new \SortableTable12();
        $t3->setRank(2);
        $t3->setScopeValue(1);
        $t3->setTitle('row2');
        $t3->save();

        $t4 = new \SortableTable12();
        $t4->setRank(1);
        $t4->setScopeValue(2);
        $t4->setTitle('row5');
        $t4->save();

        $t5 = new \SortableTable12();
        $t5->setRank(3);
        $t5->setScopeValue(1);
        $t5->setTitle('row3');
        $t5->save();

        $t6 = new \SortableTable12();
        $t6->setRank(2);
        $t6->setScopeValue(2);
        $t6->setTitle('row6');
        $t6->save();
    }

    protected function getFixturesArray()
    {
        $c = new Criteria();
        $c->addAscendingOrderByColumn(\SortableTable11Peer::RANK_COL);
        $ts = \SortableTable11Peer::doSelect($c);
        $ret = array();
        foreach ($ts as $t) {
            $ret[$t->getRank()] = $t->getTitle();
        }

        return $ret;
    }

    protected function getFixturesArrayWithScope($scope = null)
    {
        $c = new Criteria();

        if ($scope !== null) {
            $c->add(\SortableTable12Peer::SCOPE_COL, $scope);
        }

        $c->addAscendingOrderByColumn(\SortableTable12Peer::RANK_COL);

        $ts  = \SortableTable12Peer::doSelect($c);
        $ret = array();

        foreach ($ts as $t) {
            $ret[$t->getRank()] = $t->getTitle();
        }

        return $ret;
    }
}
