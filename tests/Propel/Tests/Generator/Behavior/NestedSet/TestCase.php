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
use Propel\Runtime\Configuration;
use Propel\Tests\TestCase as BaseTestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCase extends BaseTestCase
{
    protected $con;

    public function setUp()
    {
        if (!class_exists('\NestedSetEntity9')) {
            $schema = <<<XML
<database name="bookstore-behavior" defaultIdMethod="native">
    <entity name="NestedSetEntity9">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />

        <behavior name="nested_set" />
    </entity>

    <entity name="NestedSetEntity10">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="my_left_field" type="INTEGER" required="false"/>
        <field name="my_right_field" type="INTEGER" required="false"/>
        <field name="my_level_field" type="INTEGER" required="false"/>
        <field name="my_scope_field" type="INTEGER" required="false"/>

        <behavior name="nested_set">
            <parameter name="left_field" value="my_left_field" />
            <parameter name="right_field" value="my_right_field" />
            <parameter name="level_field" value="my_level_field" />
            <parameter name="use_scope" value="true" />
            <parameter name="scope_field" value="my_scope_field" />
            <parameter name="method_proxies" value="true" />
        </behavior>
    </entity>
</database>
XML;
            $this->con = QuickBuilder::buildSchema($schema);
        } else {
            $this->con = Configuration::getCurrentConfiguration();
        }
    }

    public function getConfiguration()
    {
        return $this->con;
    }

    protected function initTreeWithScope()
    {
        $this->getConfiguration()->getRepository('\NestedSetEntity10')->deleteAll();

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

        $session = $this->getConfiguration()->getSession();

        foreach ($fixtures as $key => $data) {
            $t = new \NestedSetEntity10();
            $t->setTitle($key);
            $t->setLeftValue($data[0]);
            $t->setRightValue($data[1]);
            $t->setLevel($data[2]);
            $t->setScopeValue($data[3]);
            $session->persist($t);
            $ret []= $t;
        }
        $session->commit();

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
        $this->getConfiguration()->getRepository('\NestedSetEntity9')->deleteAll();

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

        $session = $this->getConfiguration()->getSession();

        foreach ($fixtures as $key => $data) {
            $t = new \NestedSetEntity9();
            $t->setTitle($key);
            $t->setLeftValue($data[0]);
            $t->setRightValue($data[1]);
            $t->setLevel($data[2]);
            $session->persist($t);
            $ret[$key]= $t;
        }

        $session->commit();
        // reordering the results in the fixtures
        ksort($ret);

        return array_values($ret);
    }

    protected function dumpTree()
    {
        return $this->dumpNodes(\NestedSetEntity9Query::create()->orderByTitle()->find());
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
        return $this->dumpNodes(\NestedSetEntity10Query::create()->filterByMyScopeField($scope)->orderByTitle()->find());
    }
}
