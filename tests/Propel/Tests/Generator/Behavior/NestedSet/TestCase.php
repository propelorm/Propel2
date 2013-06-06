<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase as BaseTestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCase extends BaseTestCase
{
    protected $con;

    public function setUp()
    {
        if (!class_exists('NestedSetTable9')) {
            $schema = <<<XML
<database name="bookstore-behavior" defaultIdMethod="native">
    <table name="nested_set_table9">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />

        <behavior name="nested_set" />
    </table>

    <table name="nested_set_table10">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
        <column name="my_left_column" type="INTEGER" required="false"/>
        <column name="my_right_column" type="INTEGER" required="false"/>
        <column name="my_level_column" type="INTEGER" required="false"/>
        <column name="my_scope_column" type="INTEGER" required="false"/>

        <behavior name="nested_set">
            <parameter name="left_column" value="my_left_column" />
            <parameter name="right_column" value="my_right_column" />
            <parameter name="level_column" value="my_level_column" />
            <parameter name="use_scope" value="true" />
            <parameter name="scope_column" value="my_scope_column" />
            <parameter name="method_proxies" value="true" />
        </behavior>
    </table>
</database>
XML;
            $this->con = QuickBuilder::buildSchema($schema);
        }
    }

    protected function initTreeWithScope()
    {
        \Map\NestedSetTable10TableMap::doDeleteAll();

        $ret = array();
        $fixtures = array(
            't1'    => array(1, 14, 0, 1),
            't2'    => array(2, 3, 1, 1),
            't3'    => array(4, 13, 1, 1),
            't4'    => array(5, 6, 2, 1),
            't5'    => array(7, 12, 2, 1),
            't6'    => array(8, 9, 3, 1),
            't7'    => array(10, 11, 3, 1),
            't8'    => array(1, 6, 0, 2),
            't9'    => array(2, 3, 1, 2),
            't10'   => array(4, 5, 1, 2),
        );

        foreach ($fixtures as $key => $data) {
            $t = new Fixtures\PublicTable10();
            $t->setTitle($key);
            $t->setLeftValue($data[0]);
            $t->setRightValue($data[1]);
            $t->setLevel($data[2]);
            $t->setScopeValue($data[3]);
            $t->save();
            $ret []= $t;
        }

        return $ret;
    }

    /**
     * Tree used for tests
     * t1
     * |  \
     * t2 t3
     *    |  \
     *    t4 t5
     *       |  \
     *       t6 t7
     */
    protected function initTree()
    {
        \Map\NestedSetTable9TableMap::doDeleteAll();

        $ret = array();
        // shuffling the results so the db order is not the natural one
        $fixtures = array(
            't2' => array(2, 3, 1),
            't5' => array(7, 12, 2),
            't4' => array(5, 6, 2),
            't7' => array(10, 11, 3),
            't1' => array(1, 14, 0),
            't6' => array(8, 9, 3),
            't3' => array(4, 13, 1),
        );
        /* in correct order, this is:
            't1' => array(1, 14, 0),
            't2' => array(2, 3, 1),
            't3' => array(4, 13, 1),
            't4' => array(5, 6, 2),
            't5' => array(7, 12, 2),
            't6' => array(8, 9, 3),
            't7' => array(10, 11, 3),
         */
        foreach ($fixtures as $key => $data) {
            $t = new Fixtures\PublicTable9();
            $t->setTitle($key);
            $t->setLeftValue($data[0]);
            $t->setRightValue($data[1]);
            $t->setLevel($data[2]);
            $t->save();
            $ret[$key]= $t;
        }
        // reordering the results in the fixtures
        ksort($ret);

        return array_values($ret);
    }

    protected function dumpTree()
    {
        return $this->dumpNodes(\NestedSetTable9Query::create()->orderByTitle()->find());
    }

    protected function dumpNodes($nodes)
    {
        $tree = array();
        foreach ($nodes as $node) {
            $tree[$node->getTitle()] = array(
                $node->getLeftValue(),
                $node->getRightValue(),
                $node->getLevel(),
            );
        }

        return $tree;
    }

    protected function dumpTreeWithScope($scope)
    {
        return $this->dumpNodes(\NestedSetTable10Query::create()->filterByMyScopeColumn($scope)->orderByTitle()->find());
    }
}
