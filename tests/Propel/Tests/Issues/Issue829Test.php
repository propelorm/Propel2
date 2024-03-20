<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use DateTime;
use Map\Table829TableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;
use Table829;

/**
 * Regression test for https://github.com/propelorm/Propel2/issues/829
 *
 * @group database
 */
class Issue829Test extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('\Table829')) {
            $schema = '
            <database name="issue_829" defaultIdMethod="native">
                <table name="table829">
                    <column name="id" primaryKey="true" type="INTEGER"/>
                    <column name="date" primaryKey="true" type="DATE"/>
                </table>
            </database>
            ';
            QuickBuilder::buildSchema($schema);
        }
    }

    /*
     * Test if adding to instance pool doesn't throw an exception when primary key is composed of fields of types
     * that can be serialized but cannot be casted to a string (f.in. \DateTime)
     */
    /**
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testAddingToInstancePool()
    {
        $date = new DateTime();
        $test = new Table829();

        $test
            ->setId(1)
            ->setDate($date);

        Table829TableMap::addInstanceToPool($test);
    }
}
