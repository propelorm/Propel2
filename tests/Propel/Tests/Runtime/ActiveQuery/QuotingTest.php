<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\Propel;
use Propel\Tests\Quoting\AuthorQuery;
use Propel\Tests\Quoting\Group;
use Propel\Tests\Quoting\GroupQuery;
use Propel\Tests\Quoting\Map\GroupTableMap;
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
        /** @var \Propel\Runtime\Connection\ConnectionWrapper $con */
        $con = Propel::getServiceContainer()->getWriteConnection(GroupTableMap::DATABASE_NAME);

        return $con->getLastExecutedQuery();
    }

    /**
     * @return void
     */
    public function testInsertQuery()
    {
        $group = new Group();
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

    /**
     * @return void
     */
    public function testDeleteQuery()
    {
        $group = new Group();
        $group->setTitle('Test Group');
        $group->setAs(4);
        $group->save();
        $group->delete();

        $expected = $this->getSql('DELETE FROM `group` WHERE `group`.`id`=' . $group->getId());
        $this->assertEquals($expected, $this->getLastQuery());
    }

    /**
     * @return void
     */
    public function testUpdateQuery()
    {
        $group = new Group();
        $group->setTitle('Test Group');
        $group->setAs(1);
        $group->save();
        $group->setAs(2);
        $group->save();

        $expected = $this->getSql('UPDATE `group` SET `as`=2 WHERE `group`.`id`=' . $group->getId());
        $this->assertEquals($expected, $this->getLastQuery());
    }

    /**
     * @return void
     */
    public function testJoinWithNonQuotingQuery()
    {
        $groupQuery = GroupQuery::create();

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

    /**
     * @return void
     */
    public function testJoinWithQuotingQuery()
    {
        $authorQuery = AuthorQuery::create();

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

    /**
     * @return void
     */
    public function testAlias()
    {
        GroupQuery::create()
        ->setModelAlias('g', true)
        ->where('g.Id > 0')
        ->orderBy('g.As')
        ->orderBy('AuthorId')
        ->find();

        $expected = $this->getSql('SELECT `g`.`id`, `g`.`title`, `g`.`by`, `g`.`as`, `g`.`author_id` FROM `group` `g` WHERE `g`.`id` > 0 ORDER BY `g`.`as` ASC,`g`.`author_id` ASC');
        $this->assertEquals($expected, $this->getLastQuery());

        AuthorQuery::create('g')
        ->setModelAlias('g', true)
        ->groupBy('g.Id')
        ->having('g.Id > 0')
        ->find();

        if ($this->runningOnPostgreSQL()) {
            $expected = $this->getSql('SELECT g.id, g.name, g.type_id FROM quoting_author g GROUP BY g.id,g.name,g.type_id HAVING g.id > 0');
        } else {
            // note that this only works with MySQL because the query return no data, otherwise an "Expression of SELECT list is not in GROUP BY" error would be thrown
            $expected = $this->getSql('SELECT g.id, g.name, g.type_id FROM quoting_author g GROUP BY g.id HAVING g.id > 0');
        }
        $this->assertEquals($expected, $this->getLastQuery());

        GroupQuery::create('g')
        ->where('g.As > 0')
        ->find();

        $expected = $this->getSql('SELECT `group`.`id`, `group`.`title`, `group`.`by`, `group`.`as`, `group`.`author_id` FROM `group` WHERE `group`.`as` > 0');
        $this->assertEquals($expected, $this->getLastQuery());

        AuthorQuery::create('g')
        ->where('g.Id > 0')
        ->find();

        $expected = $this->getSql('SELECT quoting_author.id, quoting_author.name, quoting_author.type_id FROM quoting_author WHERE quoting_author.id > 0');
        $this->assertEquals($expected, $this->getLastQuery());
    }

    /**
     * @return void
     */
    public function testHaving()
    {
        $con = Propel::getServiceContainer()->getConnection(GroupTableMap::DATABASE_NAME);
        if( $this->runningOnMySQL())
        {
            $con->exec('SET SESSION sql_mode = "TRADITIONAL"');
        }
        GroupQuery::create()
        ->groupBy('group.As')
        ->having('group.As > 0')
        ->find($con);

        if ($this->runningOnPostgreSQL()) {
            $expected = $this->getSql('SELECT `group`.`id`, `group`.`title`, `group`.`by`, `group`.`as`, `group`.`author_id` FROM `group` GROUP BY `group`.`as`,`group`.`id`,`group`.`title`,`group`.`by`,`group`.`author_id` HAVING `group`.`as` > 0');
        } else {
            $expected = $this->getSql('SELECT `group`.`id`, `group`.`title`, `group`.`by`, `group`.`as`, `group`.`author_id` FROM `group` GROUP BY `group`.`as` HAVING `group`.`as` > 0');
        }
        $this->assertEquals($expected, $this->getLastQuery());
    }
}
