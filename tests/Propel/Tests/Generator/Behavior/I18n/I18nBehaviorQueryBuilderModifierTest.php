<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\I18n;

use I18nBehaviorTest11;
use I18nBehaviorTest11I18nQuery;
use I18nBehaviorTest11Query;
use Map\I18nBehaviorTest11I18nTableMap;
use Map\I18nBehaviorTest11TableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Tests for I18nBehavior class query modifier
 *
 * @author François Zaninotto
 * @group teschd
 */
class I18nBehaviorQueryBuilderModifierTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\I18nBehaviorTest11')) {
            $schema = <<<EOF
<database name="i18n_behavior_test_10" identifierQuoting="true">
    <table name="i18n_behavior_test_11">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="foo" type="INTEGER"/>
        <column name="bar" type="VARCHAR" size="100"/>
        <behavior name="i18n">
            <parameter name="i18n_columns" value="bar"/>
        </behavior>
    </table>
    <table name="i18n_behavior_test_12">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="foo" type="INTEGER"/>
        <column name="bar1" type="VARCHAR" size="100"/>
        <column name="bar2" type="LONGVARCHAR" lazyLoad="true"/>
        <column name="bar3" type="TIMESTAMP"/>
        <column name="bar4" type="LONGVARCHAR" description="This is the Bar4 column"/>
        <behavior name="i18n">
            <parameter name="i18n_columns" value="bar1,bar2,bar3,bar4"/>
            <parameter name="default_locale" value="fr_FR"/>
            <parameter name="locale_alias" value="culture"/>
        </behavior>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testJoinI18nUsesDefaultLocaleInJoinCondition()
    {
        $q = I18nBehaviorTest11Query::create();

        $q->joinI18n();
        $params = [];
        $sql = $q->createSelectSQL($params);
        $expectedSQL = $this->getSql('SELECT  FROM `i18n_behavior_test_11` LEFT JOIN `i18n_behavior_test_11_i18n` ON (`i18n_behavior_test_11`.`id`=`i18n_behavior_test_11_i18n`.`id` AND `i18n_behavior_test_11_i18n`.`locale` = :p1)');
        $this->assertEquals($expectedSQL, $sql);
        $this->assertEquals('en_US', $params[0]['value']);
    }

    /**
     * @return void
     */
    public function testJoinI18nUsesLocaleInJoinCondition()
    {
        $q = I18nBehaviorTest11Query::create()
            ->joinI18n('fr_FR');
        $params = [];
        $sql = $q->createSelectSQL($params);
        $expectedSQL = $this->getSql('SELECT  FROM `i18n_behavior_test_11` LEFT JOIN `i18n_behavior_test_11_i18n` ON (`i18n_behavior_test_11`.`id`=`i18n_behavior_test_11_i18n`.`id` AND `i18n_behavior_test_11_i18n`.`locale` = :p1)');
        $this->assertEquals($expectedSQL, $sql);
        $this->assertEquals('fr_FR', $params[0]['value']);
    }

    /**
     * @return void
     */
    public function testJoinI18nAcceptsARelationAlias()
    {
        $q = I18nBehaviorTest11Query::create()
            ->joinI18n('en_US', 'I18n');
        $params = [];
        $sql = $q->createSelectSQL($params);
        $expectedSQL = $this->getSql('SELECT  FROM `i18n_behavior_test_11` LEFT JOIN `i18n_behavior_test_11_i18n` `I18n` ON (`i18n_behavior_test_11`.`id`=`I18n`.`id` AND `I18n`.`locale` = :p1)');
        $this->assertEquals($expectedSQL, $sql);
        $this->assertEquals('en_US', $params[0]['value']);
    }

    /**
     * @return void
     */
    public function testJoinI18nAcceptsAJoinType()
    {
        $q = I18nBehaviorTest11Query::create()
            ->joinI18n('en_US', null, Criteria::INNER_JOIN);
        $params = [];
        $sql = $q->createSelectSQL($params);
        $expectedSQL = $this->getSql('SELECT  FROM `i18n_behavior_test_11` INNER JOIN `i18n_behavior_test_11_i18n` ON (`i18n_behavior_test_11`.`id`=`i18n_behavior_test_11_i18n`.`id` AND `i18n_behavior_test_11_i18n`.`locale` = :p1)');
        $this->assertEquals($expectedSQL, $sql);
        $this->assertEquals('en_US', $params[0]['value']);
    }

    /**
     * @return void
     */
    public function testJoinI18nCreatesACorrectQuery()
    {
        $con = Propel::getServiceContainer()->getConnection(I18nBehaviorTest11TableMap::DATABASE_NAME);
        $con->useDebug(true);
        I18nBehaviorTest11Query::create()
            ->joinI18n('fr_FR')
            ->find($con);
        $expected = $this->getSql("SELECT `i18n_behavior_test_11`.`id`, `i18n_behavior_test_11`.`foo` FROM `i18n_behavior_test_11` LEFT JOIN `i18n_behavior_test_11_i18n` ON (`i18n_behavior_test_11`.`id`=`i18n_behavior_test_11_i18n`.`id` AND `i18n_behavior_test_11_i18n`.`locale` = 'fr_FR')");
        $this->assertEquals($expected, $con->getLastExecutedQuery());
        $con->useDebug(false);
    }

    /**
     * @return void
     */
    public function testUseI18nQueryAddsTheProperJoin()
    {
        $q = I18nBehaviorTest11Query::create()
            ->useI18nQuery('fr_FR')
                ->filterByBar('bar')
            ->endUse();
        $params = [];
        $sql = $q->createSelectSQL($params);
        $expectedSQL = $this->getSql('SELECT  FROM `i18n_behavior_test_11` LEFT JOIN `i18n_behavior_test_11_i18n` ON (`i18n_behavior_test_11`.`id`=`i18n_behavior_test_11_i18n`.`id` AND `i18n_behavior_test_11_i18n`.`locale` = :p1) WHERE `i18n_behavior_test_11_i18n`.`bar`=:p2');
        $this->assertEquals($expectedSQL, $sql);
        $this->assertEquals('fr_FR', $params[0]['value']);
        $this->assertEquals('bar', $params[1]['value']);
    }

    /**
     * @return void
     */
    public function testUseI18nQueryAcceptsARelationAlias()
    {
        $q = I18nBehaviorTest11Query::create()
            ->useI18nQuery('fr_FR', 'I18n')
                ->filterByBar('bar')
            ->endUse();
        $params = [];
        $sql = $q->createSelectSQL($params);
        $expectedSQL = $this->getSql('SELECT  FROM `i18n_behavior_test_11` LEFT JOIN `i18n_behavior_test_11_i18n` `I18n` ON (`i18n_behavior_test_11`.`id`=`I18n`.`id` AND `I18n`.`locale` = :p1) WHERE `I18n`.`bar`=:p2');
        $this->assertEquals($expectedSQL, $sql);
        $this->assertEquals('fr_FR', $params[0]['value']);
        $this->assertEquals('bar', $params[1]['value']);
    }

    /**
     * @return void
     */
    public function testUseI18nQueryCreatesACorrectQuery()
    {
        $con = Propel::getServiceContainer()->getConnection(I18nBehaviorTest11TableMap::DATABASE_NAME);
        $con->useDebug(true);
        I18nBehaviorTest11Query::create()
            ->useI18nQuery('fr_FR')
                ->filterByBar('bar')
            ->endUse()
            ->find($con);
        $expected = $this->getSql("SELECT `i18n_behavior_test_11`.`id`, `i18n_behavior_test_11`.`foo` FROM `i18n_behavior_test_11` LEFT JOIN `i18n_behavior_test_11_i18n` ON (`i18n_behavior_test_11`.`id`=`i18n_behavior_test_11_i18n`.`id` AND `i18n_behavior_test_11_i18n`.`locale` = 'fr_FR') WHERE `i18n_behavior_test_11_i18n`.`bar`='bar'");
        $this->assertEquals($expected, $con->getLastExecutedQuery());
        $con->useDebug(false);
    }

    /**
     * @return void
     */
    public function testJoinWithI18nAddsTheI18nColumns()
    {
        $q = I18nBehaviorTest11Query::create()
            ->joinWithI18n();
        $params = [];
        $sql = $q->createSelectSQL($params);
        $expectedSQL = $this->getSql('SELECT `i18n_behavior_test_11`.`id`, `i18n_behavior_test_11`.`foo`, `i18n_behavior_test_11_i18n`.`id`, `i18n_behavior_test_11_i18n`.`locale`, `i18n_behavior_test_11_i18n`.`bar` FROM `i18n_behavior_test_11` LEFT JOIN `i18n_behavior_test_11_i18n` ON (`i18n_behavior_test_11`.`id`=`i18n_behavior_test_11_i18n`.`id` AND `i18n_behavior_test_11_i18n`.`locale` = :p1)');
        $this->assertEquals($expectedSQL, $sql);
        $this->assertEquals('en_US', $params[0]['value']);
    }

    /**
     * @return void
     */
    public function testJoinWithI18nDoesNotPruneResultsWithoutTranslation()
    {
        I18nBehaviorTest11Query::create()->deleteAll();
        I18nBehaviorTest11I18nQuery::create()->deleteAll();
        $o = new I18nBehaviorTest11();
        $o->setFoo(123);
        $o->save();
        $res = I18nBehaviorTest11Query::create()
            ->joinWithI18n('en_US')
            ->findOne();
        $this->assertEquals($o, $res);
    }

    /**
     * @return void
     */
    public function testJoinWithI18nPrunesResultsWithoutTranslationWhenUsingInnerJoin()
    {
        I18nBehaviorTest11Query::create()->deleteAll();
        I18nBehaviorTest11I18nQuery::create()->deleteAll();
        $o = new I18nBehaviorTest11();
        $o->setFoo(123);
        $o->save();
        $res = I18nBehaviorTest11Query::create()
            ->joinWithI18n('en_US', Criteria::INNER_JOIN)
            ->findOne();
        $this->assertNull($res);
    }

    /**
     * @return void
     */
    public function testJoinWithI18nHydratesRelatedObject()
    {
        $con = Propel::getServiceContainer()->getConnection(I18nBehaviorTest11TableMap::DATABASE_NAME);
        $con->useDebug(true);
        I18nBehaviorTest11Query::create()->deleteAll();
        I18nBehaviorTest11I18nQuery::create()->deleteAll();
        $o = new I18nBehaviorTest11();
        $o->setFoo(123);
        $o->setLocale('en_US');
        $o->setBar('hello');
        $o->setLocale('fr_FR');
        $o->setBar('bonjour');
        $o->save();
        I18nBehaviorTest11TableMap::clearInstancePool();
        I18nBehaviorTest11I18nTableMap::clearInstancePool();
        $o = I18nBehaviorTest11Query::create()
            ->joinWithI18n('en_US')
            ->findOne($con);
        $count = $con->getQueryCount();
        $translation = $o->getTranslation('en_US', $con);
        $this->assertEquals($count, $con->getQueryCount());
        $this->assertEquals('hello', $translation->getBar());
    }

    /**
     * @return void
     */
    public function testJoinWithI18nSetsTheLocaleOnResults()
    {
        I18nBehaviorTest11Query::create()->deleteAll();
        I18nBehaviorTest11I18nQuery::create()->deleteAll();
        $o = new I18nBehaviorTest11();
        $o->setFoo(123);
        $o->setLocale('en_US');
        $o->setBar('hello');
        $o->setLocale('fr_FR');
        $o->setBar('bonjour');
        $o->save();
        $o1 = I18nBehaviorTest11Query::create()
            ->joinWithI18n('en_US')
            ->findOne();
        $this->assertEquals('en_US', $o1->getLocale());
        $o2 = I18nBehaviorTest11Query::create()
            ->joinWithI18n('fr_FR')
            ->findOne();
        $this->assertEquals('fr_FR', $o2->getLocale());
    }

    /**
     * @return void
     */
    public function testJoinWithI18nAndLimitDoesNotThrowException()
    {
        $res = I18nBehaviorTest11Query::create()
            ->joinWithI18n('en_US')
            ->limit(2)
            ->find();
        $this->assertInstanceOf('\Propel\Runtime\Collection\ObjectCollection', $res);
    }

    // This is not a desired behavior, but there is no way to overcome it
    // because if we don't issue a database query when the collection exists
    // then there is no way to avoid duplicates when adding translations.
    // use case:
    // $o = new Object();
    // $t1 = new Translation();
    // $o->setTranslation($t2, 'en_US'); // this is what happens during joined hydration
    // now the translation collection exists
    // $t2 = $o->getTranslation('fr_FR'); // we MUST issue a query here
    /**
     * @return void
     */
    public function testJoinWithI18nDoesNotExecuteAdditionalQueryWhenNoTranslationIsFound()
    {
        $this->markTestSkipped();

        $con = Propel::getServiceContainer()->getConnection(I18nBehaviorTest11TableMap::DATABASE_NAME);
        $con->useDebug(true);
        I18nBehaviorTest11Query::create()->deleteAll();
        I18nBehaviorTest11I18nQuery::create()->deleteAll();
        $o = new I18nBehaviorTest11();
        $o->save();
        $o = I18nBehaviorTest11Query::create()
            ->joinWithI18n('en_US')
            ->findOne($con);
        $count = $con->getQueryCount();
        $translation = $o->getTranslation('en_US', $con);
        $this->assertEquals($count, $con->getQueryCount());
    }
}
