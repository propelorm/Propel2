<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Test class for Criteria fluid operators.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class CriteriaFluidOperatorTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testDefault()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo', 'bar');
        $expected = 'SELECT  FROM  WHERE foo=:p1';

        $params = [];
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testDefaultOperatorIsAnd()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo1', 'bar1');
        $c->addUsingOperator('foo2', 'bar2');
        $expected = 'SELECT  FROM  WHERE foo1=:p1 AND foo2=:p2';

        $params = [];
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testOrOverridesDefaultOperator()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo1', 'bar1');
        $c->_or();
        $c->addUsingOperator('foo2', 'bar2');
        $expected = 'SELECT  FROM  WHERE (foo1=:p1 OR foo2=:p2)';

        $params = [];
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testOrWithNoExistingCriterionIsAnd()
    {
        $c = new Criteria();
        $c->_or();
        $c->addUsingOperator('foo', 'bar');
        $expected = 'SELECT  FROM  WHERE foo=:p1';

        $params = [];
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testOrWithNoExistingCriterionIsAnd2()
    {
        $c = new Criteria();
        $c->_or();
        $c->addUsingOperator('foo1', 'bar');
        $c->addUsingOperator('foo2', 'bar2');
        $expected = 'SELECT  FROM  WHERE foo1=:p1 AND foo2=:p2';

        $params = [];
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testOrCanBeCalledSeveralTimes()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo1', 'bar1');
        $c->_or();
        $c->addUsingOperator('foo2', 'bar2');
        $c->_or();
        $c->addUsingOperator('foo3', 'bar3');
        $expected = 'SELECT  FROM  WHERE ((foo1=:p1 OR foo2=:p2) OR foo3=:p3)';

        $params = [];
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testaddUsingOperatorResetsDefaultOperator()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo1', 'bar1');
        $c->_or();
        $c->addUsingOperator('foo2', 'bar2');
        $c->addUsingOperator('foo3', 'bar3');
        $expected = 'SELECT  FROM  WHERE (foo1=:p1 OR foo2=:p2) AND foo3=:p3';

        $params = [];
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }
}
