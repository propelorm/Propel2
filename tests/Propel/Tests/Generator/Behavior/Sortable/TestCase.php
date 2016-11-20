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

use Propel\Runtime\Configuration;
use Propel\Tests\TestCase as BaseTestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class TestCase extends BaseTestCase
{
    /**
     * @var Configuration
     */
    protected $configuration;

    public function setUp()
    {
        parent::setUp();

        if (!class_exists('\SortableEntity11')) {
            $schema = <<<XML
<database name="Sortable-testCase" defaultIdMethod="native">

    <entity name="SortableEntity11">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />

        <behavior name="sortable" />
    </entity>

    <entity name="SortableEntity12">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="position" type="INTEGER" />

        <behavior name="sortable">
            <parameter name="rank_field" value="position" />
            <parameter name="use_scope" value="true" />
            <parameter name="scope_field" value="my_scope_field" />
        </behavior>
    </entity>

    <entity name="SortableMultiScopes">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="categoryId" required="true" type="INTEGER" />
        <field name="subCategoryId" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="position" type="INTEGER" />
        <behavior name="sortable">
            <parameter name="rank_field" value="position" />
            <parameter name="use_scope" value="true" />
            <parameter name="scope_field" value="categoryId" />
            <parameter name="scope_field" value="subCategoryId" />
        </behavior>
    </entity>

    <entity name="SortableMultiCommaScopes">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="categoryId" required="true" type="INTEGER" />
        <field name="subCategoryId" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="position" type="INTEGER" />
        <behavior name="sortable">
            <parameter name="rank_field" value="position" />
            <parameter name="use_scope" value="true" />
            <parameter name="scope_field" value="categoryId, subCategoryId" />
        </behavior>
    </entity>

</database>
XML;
            QuickBuilder::buildSchema($schema);
        }

        $this->configuration = Configuration::getCurrentConfiguration();
    }
    
    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getRepository($entityName)
    {
        return $this->getConfiguration()->getRepository($entityName);
    }

    protected function populateEntity11()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $repository->deleteAll();

        $t1 = new \SortableEntity11();
        $t1->setRank(1);
        $t1->setTitle('row1');
        $repository->save($t1);

        $t2 = new \SortableEntity11();
        $t2->setRank(4);
        $t2->setTitle('row4');
        $repository->save($t2);

        $t3 = new \SortableEntity11();
        $t3->setRank(2);
        $t3->setTitle('row2');
        $repository->save($t3);

        $t4 = new \SortableEntity11();
        $t4->setRank(3);
        $t4->setTitle('row3');
        $repository->save($t4);
    }

    protected function populateEntity12()
    {
        /* List used for tests
         scope=1   scope=2   scope=null
         row1      row5      row7
         row2      row6      row8
         row3                row9
         row4                row10
        */

        $repository = $this->getRepository('\SortableEntity12');
        $repository->deleteAll();

        $t1 = new \SortableEntity12();
        $t1->setRank(1);
        $t1->setScopeValue(1);
        $t1->setTitle('row1');
        $repository->save($t1);

        $t2 = new \SortableEntity12();
        $t2->setRank(4);
        $t2->setScopeValue(1);
        $t2->setTitle('row4');
        $repository->save($t2);

        $t3 = new \SortableEntity12();
        $t3->setRank(2);
        $t3->setScopeValue(1);
        $t3->setTitle('row2');
        $repository->save($t3);

        $t4 = new \SortableEntity12();
        $t4->setRank(1);
        $t4->setScopeValue(2);
        $t4->setTitle('row5');
        $repository->save($t4);

        $t5 = new \SortableEntity12();
        $t5->setRank(3);
        $t5->setScopeValue(1);
        $t5->setTitle('row3');
        $repository->save($t5);

        $t6 = new \SortableEntity12();
        $t6->setRank(2);
        $t6->setScopeValue(2);
        $t6->setTitle('row6');
        $repository->save($t6);

        $t7 = new \SortableEntity12();
        $t7->setRank(1);
        $t7->setTitle('row7');
        $repository->save($t7);

        $t8 = new \SortableEntity12();
        $t8->setRank(2);
        $t8->setTitle('row8');
        $repository->save($t8);

        $t9 = new \SortableEntity12();
        $t9->setRank(3);
        $t9->setTitle('row9');
        $repository->save($t9);

        $t10 = new \SortableEntity12();
        $t10->setRank(4);
        $t10->setTitle('row10');
        $repository->save($t10);
    }

    protected function getFixturesArray()
    {
        $ts = \SortableEntity11Query::create()->orderByRank()->find();
        $ret = array();
        foreach ($ts as $t) {
            $ret[$t->getRank()] = $t->getTitle();
        }

        return $ret;
    }

    protected function getFixturesArrayWithScope($scope = null)
    {
        $ts  = \SortableEntity12Query::create()->filterByMyScopeField($scope)->orderByPosition()->find();
        $ret = array();
        foreach ($ts as $t) {
            $ret[$t->getRank()] = $t->getTitle();
        }

        return $ret;
    }
}
