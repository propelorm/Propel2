<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Persister\Sql;

use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Tests\TestCase;

/**
 * Class SqlitePersisterTest
 *
 * @package Propel\Tests\Runtime\Persister\Sql
 */
class SQLitePersisterTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('\Song')) {
            $schema = <<<EOF
<database name="sqlite_persister_test" identifierQuoting="true" activeRecord="true">
    <entity name="Singer">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="Name" type="VARCHAR" />
    </entity>
    <entity name="Song">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="title" type="VARCHAR" />
        <relation target="Singer" onDelete="setnull" onUpdate="cascade"/>
    </entity>
    <entity name="Show">
        <field name="id" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" />
    </entity>
</database>
EOF;

            QuickBuilder::buildSchema($schema);
        }
    }

    public function testPkIncremetsAfterDeletion()
    {
        $singer =new \Singer();
        $singer->setName('Paul McCartney');
        $singer->save();

        $singerQuery =new \SingerQuery();
        $singers = $singerQuery->find();

        $this->assertEquals(1, count($singers));
        $this->assertEquals(1, $singers[0]->getId());

        $singerQuery->deleteAll();

        $singer =new \Singer();
        $singer->setName('John McLaughlin');
        $singer->save();

        $singers1 = $singerQuery->find();

        $this->assertEquals(1, count($singers1));
        $this->assertEquals(2, $singers1[0]->getId());
    }

    /*
     * This test failed in previous versions of SqlitePersister class,
     * for a  `FOREIGN KEY violation` Sqlite error.
     */
    public function testInsertAfterDelete()
    {
        $singerQuery =new \SingerQuery();
        $songQuery = new \SongQuery();
        $singerQuery->deleteAll();
        $songQuery->deleteAll();

        $singer = new \Singer();
        $song = new \Song();
        $singer->setName('Dire Straits');
        $song->setTitle('Sultans of swing');
        $song->setSinger($singer);
        $song->save();

        $this->assertEquals(1, $songQuery->count(),'1 Song');
        $this->assertEquals(1, $singerQuery->count(), '1 Singer');

        $singerQuery->deleteAll();
        $songQuery->deleteAll();

        $singer = new \Singer();
        $song = new \Song();
        $singer->setName('ACDC');
        $song->setTitle('Back in Black');
        $song->setSinger($singer);
        $song->save();

        $this->assertEquals(1, $songQuery->count(),'1 Song');
        $this->assertEquals(1, $singerQuery->count(), '1 Singer');
    }

    public function testSequenceTableNotExistant()
    {
        $connection = QuickBuilder::$configuration
            ->getConnectionManager(\Map\ShowEntityMap::DATABASE_NAME)
            ->getWriteConnection();

        //Add 2 records
        $show = new \Show();
        $show->setTitle('Merry Christmas');
        $show->save();

        $this->assertEquals(1, $show->getId());
        $this->assertEquals(1, $this->getAutoincrementId($connection));
        $this->assertEquals(1, \ShowQuery::create()->count());

        $show1 = new \Show();
        $show1->setTitle('Happy New Year');
        $show1->save();

        $this->assertEquals(2, $show1->getId());
        $this->assertEquals(2, $this->getAutoincrementId($connection));
        $this->assertEquals(2, \ShowQuery::create()->count());

        //Delete the sequence table
        $this->assertTrue($this->deleteSequenceTable($connection));
        $this->assertEquals(0, $this->getAutoincrementId($connection));

        //Add once more record and verify the Id is 3, as it may, and the record is
        //correctly added
        $show2 = new \Show();
        $show2->setTitle('Halloween');
        $show2->save();

        $this->assertEquals(3, $show2->getId());
        $this->assertEquals(3, \ShowQuery::create()->count());

        //When save a record, Sqlite automatically re-creates `sqlite_sequence` entry,
        // with the correct autoincrement value
        $this->assertEquals(3, $this->getAutoincrementId($connection));
    }

    private function getAutoincrementId(ConnectionInterface $connection)
    {
        $tableName = \Map\ShowEntityMap::FQ_TABLE_NAME;
        $stmt = $connection->prepare("SELECT seq FROM sqlite_sequence WHERE name = '$tableName'");
        $stmt->execute();

        return (integer) $stmt->fetchColumn();
    }

    private function deleteSequenceTable(ConnectionInterface $connection)
    {
        $tableName = \Map\ShowEntityMap::FQ_TABLE_NAME;
        $stmt = $connection->prepare("DELETE FROM sqlite_sequence WHERE name = '$tableName'");

        return $stmt->execute();
    }
}