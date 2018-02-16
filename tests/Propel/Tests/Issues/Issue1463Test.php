<?php

namespace Propel\Tests\Issues;

use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;


/**
 * Regression tests for a SQL injection vulnerability with `limit()`.
 *
 * @link https://github.com/propelorm/Propel2/issues/1463
 */
class Issue1463Test extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        if (class_exists('\Issue1463Item')) {
            return;
        }

        $schema = <<<END
<database name="issue_1463">
    <table name="issue_1463_item">
        <column name="id" type="INTEGER" size="10" sqlType="INT(10) UNSIGNED" primaryKey="true" required="true" autoIncrement="true" />
        <column name="name" type="VARCHAR" size="32" required="true" />
    </table>
</database>
END;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->buildClasses(null, true);
        Propel::getServiceContainer()->setAdapter('issue_1463', new MysqlAdapter());
    }

    /**
     * Verifies that the correct SQL is generated for queries that use `limit()`.
     *
     * @dataProvider dataLimit
     */
    public function testLimit($limit, $expectedSql)
    {
        $query = \Issue1463ItemQuery::create()->limit($limit);

        $params = [];
        $actualSql = $query->createSelectSql($params);

        $this->assertEquals($expectedSql, $actualSql, 'Generated SQL does not match expected SQL');
    }

    public function dataLimit()
    {
        return array(

            /*
                Valid limits
             */

            'Zero' => array(
                'limit'       => 0,
                'expectedSql' => 'SELECT  FROM  LIMIT 0'
            ),

            'Small integer' => array(
                'limit'       => 38427,
                'expectedSql' => 'SELECT  FROM  LIMIT 38427'
            ),
            'Small integer as a string' => array(
                'limit'       => '38427',
                'expectedSql' => 'SELECT  FROM  LIMIT 38427'
            ),

            'Large integer' => array(
                'limit'       => 9223372036854775807,
                'expectedSql' => 'SELECT  FROM  LIMIT 9223372036854775807'
            ),
            'Large integer as a string' => array(
                'limit'       => '9223372036854775807',
                'expectedSql' => 'SELECT  FROM  LIMIT 9223372036854775807'
            ),

            'Decimal value' => array(
                'limit'       => 123.9,
                'expectedSql' => 'SELECT  FROM  LIMIT 123'
            ),
            'Decimal value as a string' => array(
                'limit'       => '123.9',
                'expectedSql' => 'SELECT  FROM  LIMIT 123'
            ),

            /*
                Invalid limits
             */

            'Negative value' => array(
                'limit'       => -1,
                'expectedSql' => 'SELECT  FROM '
            ),
            'Non-numeric string' => array(
                'limit'       => 'foo',
                'expectedSql' => 'SELECT  FROM  LIMIT 0'
            ),
            'Injected SQL' => array(
                'limit'       => '3;DROP TABLE abc',
                'expectedSql' => 'SELECT  FROM  LIMIT 3'
            ),
        );
    }

    /**
     * Verifies that the correct SQL is generated for queries that use `offset()`.
     *
     * @dataProvider dataOffset
     */
    public function testOffset($offset, $expectedSql)
    {
        $query = \Issue1463ItemQuery::create()->offset($offset);

        $params = [];
        $actualSql = $query->createSelectSql($params);

        $this->assertEquals($expectedSql, $actualSql, 'Generated SQL does not match expected SQL');
    }

    public function dataOffset()
    {
        return array(

            /*
                Valid offsets
             */

            'Zero' => array(
                'offset'      => 0,
                'expectedSql' => 'SELECT  FROM '
            ),

            'Small integer' => array(
                'offset'      => 38427,
                'expectedSql' => 'SELECT  FROM  LIMIT 38427, 18446744073709551615'
            ),
            'Small integer as a string' => array(
                'offset'      => '38427',
                'expectedSql' => 'SELECT  FROM  LIMIT 38427, 18446744073709551615'
            ),

            'Large integer' => array(
                'offset'      => 9223372036854775807,
                'expectedSql' => 'SELECT  FROM  LIMIT 9223372036854775807, 18446744073709551615'
            ),
            'Large integer as a string' => array(
                'offset'      => '9223372036854775807',
                'expectedSql' => 'SELECT  FROM  LIMIT 9223372036854775807, 18446744073709551615'
            ),

            'Decimal value' => array(
                'offset'      => 123.9,
                'expectedSql' => 'SELECT  FROM  LIMIT 123, 18446744073709551615'
            ),
            'Decimal value as a string' => array(
                'offset'      => '123.9',
                'expectedSql' => 'SELECT  FROM  LIMIT 123, 18446744073709551615'
            ),

            /*
                Invalid offsets
             */

            'Negative value' => array(
                'offset'      => -1,
                'expectedSql' => 'SELECT  FROM '
            ),
            'Non-numeric string' => array(
                'offset'      => 'foo',
                'expectedSql' => 'SELECT  FROM '
            ),
            'Injected SQL' => array(
                'offset'      => '3;DROP TABLE abc',
                'expectedSql' => 'SELECT  FROM  LIMIT 3, 18446744073709551615'
            ),
        );
    }

    /**
     * Verifies that the correct SQL is generated for queries that use both `offset()` and `limit()`.
     *
     * @dataProvider dataOffsetAndLimit
     */
    public function testOffsetAndLimit($offset, $expectedSql)
    {
        $query = \Issue1463ItemQuery::create()->offset($offset)->limit(999);

        $params = [];
        $actualSql = $query->createSelectSql($params);

        $this->assertEquals($expectedSql, $actualSql, 'Generated SQL does not match expected SQL');
    }

    public function dataOffsetAndLimit()
    {
        return array(

            /*
                Valid offsets
             */

            'Zero' => array(
                'offset'      => 0,
                'expectedSql' => 'SELECT  FROM  LIMIT 999'
            ),

            'Small integer' => array(
                'offset'      => 38427,
                'expectedSql' => 'SELECT  FROM  LIMIT 38427, 999'
            ),
            'Small integer as a string' => array(
                'offset'      => '38427',
                'expectedSql' => 'SELECT  FROM  LIMIT 38427, 999'
            ),

            'Large integer' => array(
                'offset'      => 9223372036854775807,
                'expectedSql' => 'SELECT  FROM  LIMIT 9223372036854775807, 999'
            ),
            'Large integer as a string' => array(
                'offset'      => '9223372036854775807',
                'expectedSql' => 'SELECT  FROM  LIMIT 9223372036854775807, 999'
            ),

            'Decimal value' => array(
                'offset'      => 123.9,
                'expectedSql' => 'SELECT  FROM  LIMIT 123, 999'
            ),
            'Decimal value as a string' => array(
                'offset'      => '123.9',
                'expectedSql' => 'SELECT  FROM  LIMIT 123, 999'
            ),

            /*
                Invalid offsets
             */

            'Negative value' => array(
                'offset'      => -1,
                'expectedSql' => 'SELECT  FROM  LIMIT 999'
            ),
            'Non-numeric string' => array(
                'offset'      => 'foo',
                'expectedSql' => 'SELECT  FROM  LIMIT 999'
            ),
            'Injected SQL' => array(
                'offset'      => '3;DROP TABLE abc',
                'expectedSql' => 'SELECT  FROM  LIMIT 3, 999'
            ),
        );
    }

}
