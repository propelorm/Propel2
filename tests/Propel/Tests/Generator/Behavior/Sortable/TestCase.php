<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable11TableMap;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable12TableMap;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable13TableMap;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable14TableMap;
use Propel\Tests\Bookstore\Behavior\SortableTable11;
use Propel\Tests\Bookstore\Behavior\SortableTable11Query;
use Propel\Tests\Bookstore\Behavior\SortableTable12;
use Propel\Tests\Bookstore\Behavior\SortableTable12Query;
use Propel\Tests\Bookstore\Behavior\SortableTable13;
use Propel\Tests\Bookstore\Behavior\SortableTable14;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCase extends TestCaseFixturesDatabase
{
    /**
     * @return void
     */
    protected function populateTable11()
    {
        SortableTable11TableMap::doDeleteAll();

        $t1 = new SortableTable11();
        $t1->setRank(1);
        $t1->setTitle('row1');
        $t1->save();

        $t2 = new SortableTable11();
        $t2->setRank(4);
        $t2->setTitle('row4');
        $t2->save();

        $t3 = new SortableTable11();
        $t3->setRank(2);
        $t3->setTitle('row2');
        $t3->save();

        $t4 = new SortableTable11();
        $t4->setRank(3);
        $t4->setTitle('row3');
        $t4->save();
    }

    /**
     * @return void
     */
    protected function populateTable12()
    {
        /* List used for tests
         scope=1   scope=2   scope=null
         row1      row5      row7
         row2      row6      row8
         row3                row9
         row4                row10
        */

        SortableTable12TableMap::doDeleteAll();

        $t1 = new SortableTable12();
        $t1->setRank(1);
        $t1->setScopeValue(1);
        $t1->setTitle('row1');
        $t1->save();

        $t2 = new SortableTable12();
        $t2->setRank(4);
        $t2->setScopeValue(1);
        $t2->setTitle('row4');
        $t2->save();

        $t3 = new SortableTable12();
        $t3->setRank(2);
        $t3->setScopeValue(1);
        $t3->setTitle('row2');
        $t3->save();

        $t4 = new SortableTable12();
        $t4->setRank(1);
        $t4->setScopeValue(2);
        $t4->setTitle('row5');
        $t4->save();

        $t5 = new SortableTable12();
        $t5->setRank(3);
        $t5->setScopeValue(1);
        $t5->setTitle('row3');
        $t5->save();

        $t6 = new SortableTable12();
        $t6->setRank(2);
        $t6->setScopeValue(2);
        $t6->setTitle('row6');
        $t6->save();

        $t7 = new SortableTable12();
        $t7->setRank(1);
        $t7->setTitle('row7');
        $t7->save();
        $t8 = new SortableTable12();
        $t8->setRank(2);
        $t8->setTitle('row8');
        $t8->save();
        $t9 = new SortableTable12();
        $t9->setRank(3);
        $t9->setTitle('row9');
        $t9->save();
        $t10 = new SortableTable12();
        $t10->setRank(4);
        $t10->setTitle('row10');
        $t10->save();
    }

    /**
     * @return void
     */
    protected function populateTable13()
    {
        SortableTable13TableMap::doDeleteAll();

        $t1 = new SortableTable13();
        $t1->setTitle('row1');
        $t1->setStyle(SortableTable13TableMap::COL_STYLE_NOVEL);
        $t1->save();

        $t2 = new SortableTable13();
        $t2->setTitle('row2');
        $t2->setStyle(SortableTable13TableMap::COL_STYLE_NOVEL);
        $t2->save();

        $t3 = new SortableTable13();
        $t3->setTitle('row3');
        $t3->setStyle(SortableTable13TableMap::COL_STYLE_ESSAY);
        $t3->save();

        $t4 = new SortableTable13();
        $t4->setTitle('row4');
        $t4->setStyle(SortableTable13TableMap::COL_STYLE_ESSAY);
        $t4->save();
    }

    /**
     * @return void
     */
    protected function populateTable14()
    {
        SortableTable14TableMap::doDeleteAll();

        $t1 = new SortableTable14();
        $t1->setTitle('row1');
        $t1->setStyle2(SortableTable14TableMap::COL_STYLE2_NOVEL);
        $t1->save();

        $t2 = new SortableTable14();
        $t2->setTitle('row2');
        $t2->setStyle2(SortableTable14TableMap::COL_STYLE2_NOVEL);
        $t2->save();

        $t3 = new SortableTable14();
        $t3->setTitle('row3');
        $t3->setStyle2(SortableTable14TableMap::COL_STYLE2_ESSAY);
        $t3->save();

        $t4 = new SortableTable14();
        $t4->setTitle('row4');
        $t4->setStyle2(SortableTable14TableMap::COL_STYLE2_ESSAY);
        $t4->save();
    }

    protected function getFixturesArray()
    {
        $ts = SortableTable11Query::create()->orderByRank()->find();
        $ret = [];
        foreach ($ts as $t) {
            $ret[$t->getRank()] = $t->getTitle();
        }

        return $ret;
    }

    protected function getFixturesArrayWithScope($scope = null)
    {
        $c = new Criteria();
        $c->add(SortableTable12TableMap::SCOPE_COL, $scope);
        $ts = SortableTable12Query::create(null, $c)->orderByPosition()->find();
        $ret = [];

        foreach ($ts as $t) {
            $ret[$t->getRank()] = $t->getTitle();
        }

        return $ret;
    }
}
