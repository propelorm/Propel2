<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Propel\Runtime\Configuration;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity10;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity10Query;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity9;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity9Query;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCase extends TestCaseFixturesDatabase
{
    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return Configuration::getCurrentConfiguration();
    }

    protected function initTreeWithScope()
    {
        $this->getConfiguration()->getRepository(NestedSetEntity10::class)->deleteAll();

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
            $t = new NestedSetEntity10();
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
        $this->getConfiguration()->getRepository(NestedSetEntity9::class)->deleteAll();

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
            $t = new NestedSetEntity9();
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
        return $this->dumpNodes(NestedSetEntity9Query::create()->orderByTitle()->find());
    }

    /**
     * @param NestedSetEntity9[] $nodes
     *
     * @return array
     */
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
        return $this->dumpNodes(NestedSetEntity10Query::create()->filterByMyScopeField($scope)->orderByTitle()->find());
    }
}
