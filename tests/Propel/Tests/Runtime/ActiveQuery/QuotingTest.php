<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Test class for identifierQuoting.
 *
 * @group database
 */
class QuotingTest extends TestCaseFixturesDatabase
{

    protected function getLastQuery()
    {
        /** @var ConnectionWrapper $con */
        $con = Propel::getServiceContainer()->getWriteConnection(\Propel\Tests\Quoting\Map\GroupTableMap::DATABASE_NAME);
        return $con->getLastExecutedQuery();
    }

    public function testInsertQuery()
    {
        $group = new \Propel\Tests\Quoting\Group();
        $group->setTitle('Test Group');
        $group->setAs(3);
        $group->save();

        $this->assertGreaterThan(0, $group->getId());

        if ($this->runningOnPostgreSQL()) {
            $expected = $this->getSql(sprintf("INSERT INTO `group` (`id`, `title`, `as`) VALUES (%s, 'Test Group', 3)", $group->getId()));
        } else {
            $expected = $this->getSql("INSERT INTO `group` (`id`, `title`, `as`) VALUES (NULL, 'Test Group', 3)");
        }
        $this->assertEquals($expected, $this->getLastQuery());
    }

    public function testDeleteQuery()
    {
        $group = new \Propel\Tests\Quoting\Group();
        $group->setTitle('Test Group');
        $group->setAs(4);
        $group->save();
        $group->delete();

        $expected = $this->getSql("DELETE FROM `group` WHERE `group`.`id`=" . $group->getId());
        $this->assertEquals($expected, $this->getLastQuery());
    }

    public function testUpdateQuery()
    {
        $group = new \Propel\Tests\Quoting\Group();
        $group->setTitle('Test Group');
        $group->setAs(1);
        $group->save();
        $group->setAs(2);
        $group->save();

        $expected = $this->getSql("UPDATE `group` SET `as`=2 WHERE `group`.`id`=" . $group->getId());
        $this->assertEquals($expected, $this->getLastQuery());
    }

    public function testJoinWithNonQuotingQuery()
    {
        $groupQuery = \Propel\Tests\Quoting\GroupQuery::create();

        $groupQuery
        ->joinAuthor()
        ->useAuthorQuery()
            ->filterByName('Author filter')
        ->endUse()
        ->with('Author')
        ->find();

        $expected = $this->getSql("SELECT `group`.`id`, `group`.`title`, `group`.`by`, `group`.`as`, `group`.`author_id`, quoting_author.id, quoting_author.name, quoting_author.type_id FROM `group` LEFT JOIN quoting_author ON (`group`.`author_id`=quoting_author.id) WHERE quoting_author.name='Author filter'");
        $this->assertEquals($expected, $this->getLastQuery());
    }


    public function testJoinWithQuotingQuery()
    {
        $authorQuery = \Propel\Tests\Quoting\AuthorQuery::create();

        $authorQuery
        ->joinAuthorType()
        ->useAuthorTypeQuery()
            ->filterByTitle('Author type title')
        ->endUse()
        ->with('AuthorType')
        ->find();

        $expected = $this->getSql("SELECT quoting_author.id, quoting_author.name, quoting_author.type_id, `quoting_author_type`.`id`, `quoting_author_type`.`title` FROM quoting_author LEFT JOIN `quoting_author_type` ON (quoting_author.type_id=`quoting_author_type`.`id`) WHERE `quoting_author_type`.`title`='Author type title'");
        $this->assertEquals($expected, $this->getLastQuery());
    }

    public function testAlias()
    {
        \Propel\Tests\Quoting\GroupQuery::create()
            ->setModelAlias('g', true)
            ->where('g.Id > 0')
            ->orderBy('g.As')
            ->orderBy('AuthorId')
            ->find();

        $expected = $this->getSql("SELECT `g`.`id`, `g`.`title`, `g`.`by`, `g`.`as`, `g`.`author_id` FROM `group` `g` WHERE `g`.`id` > 0 ORDER BY `g`.`as` ASC,`g`.`author_id` ASC");
        $this->assertEquals($expected, $this->getLastQuery());

        \Propel\Tests\Quoting\AuthorQuery::create('g')
            ->setModelAlias('g', true)
            ->groupBy('g.Id')
            ->having('g.Id > 0')
            ->find();

        if ($this->runningOnPostgreSQL()) {
            $expected = $this->getSql("SELECT g.id, g.name, g.type_id FROM quoting_author g GROUP BY g.id,g.name,g.type_id HAVING g.id > 0");
        } else {
            $expected = $this->getSql( "SELECT g.id, g.name, g.type_id FROM quoting_author g GROUP BY g.id HAVING g.id > 0");
        }
        $this->assertEquals($expected, $this->getLastQuery());

        \Propel\Tests\Quoting\GroupQuery::create('g')
            ->where('g.As > 0')
            ->find();

        $expected = $this->getSql("SELECT `group`.`id`, `group`.`title`, `group`.`by`, `group`.`as`, `group`.`author_id` FROM `group` WHERE `group`.`as` > 0");
        $this->assertEquals($expected, $this->getLastQuery());

        \Propel\Tests\Quoting\AuthorQuery::create('g')
            ->where('g.Id > 0')
            ->find();

        $expected = $this->getSql("SELECT quoting_author.id, quoting_author.name, quoting_author.type_id FROM quoting_author WHERE quoting_author.id > 0");
        $this->assertEquals($expected, $this->getLastQuery());
    }

    public function testHaving()
    {
        \Propel\Tests\Quoting\GroupQuery::create()
            ->groupBy('group.As')
            ->having('group.As > 0')
            ->find();

        if ($this->runningOnPostgreSQL()) {
            $expected = $this->getSql("SELECT `group`.`id`, `group`.`title`, `group`.`by`, `group`.`as`, `group`.`author_id` FROM `group` GROUP BY `group`.`as`,`group`.`id`,`group`.`title`,`group`.`by`,`group`.`author_id` HAVING `group`.`as` > 0");
        } else {
            $expected = $this->getSql("SELECT `group`.`id`, `group`.`title`, `group`.`by`, `group`.`as`, `group`.`author_id` FROM `group` GROUP BY `group`.`as` HAVING `group`.`as` > 0");
        }
        $this->assertEquals($expected, $this->getLastQuery());

    }


}