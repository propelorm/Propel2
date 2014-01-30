<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Test class for Criteria fluid operators.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class CriteriaFluidOperatorTest extends BookstoreTestBase
{
    public function testDefault()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo', 'bar');
        $expected = 'SELECT  FROM  WHERE foo=:p1';

        $params = array();
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    public function testDefaultOperatorIsAnd()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo1', 'bar1');
        $c->addUsingOperator('foo2', 'bar2');
        $expected = 'SELECT  FROM  WHERE foo1=:p1 AND foo2=:p2';

        $params = array();
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    public function testOrOverridesDefaultOperator()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo1', 'bar1');
        $c->_or();
        $c->addUsingOperator('foo2', 'bar2');
        $expected = 'SELECT  FROM  WHERE (foo1=:p1 OR foo2=:p2)';

        $params = array();
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    public function testOrWithNoExistingCriterionIsAnd()
    {
        $c = new Criteria();
        $c->_or();
        $c->addUsingOperator('foo', 'bar');
        $expected = 'SELECT  FROM  WHERE foo=:p1';

        $params = array();
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    public function testOrWithNoExistingCriterionIsAnd2()
    {
        $c = new Criteria();
        $c->_or();
        $c->addUsingOperator('foo1', 'bar');
        $c->addUsingOperator('foo2', 'bar2');
        $expected = 'SELECT  FROM  WHERE foo1=:p1 AND foo2=:p2';

        $params = array();
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    public function testOrCanBeCalledSeveralTimes()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo1', 'bar1');
        $c->_or();
        $c->addUsingOperator('foo2', 'bar2');
        $c->_or();
        $c->addUsingOperator('foo3', 'bar3');
        $expected = 'SELECT  FROM  WHERE ((foo1=:p1 OR foo2=:p2) OR foo3=:p3)';

        $params = array();
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }

    public function testaddUsingOperatorResetsDefaultOperator()
    {
        $c = new Criteria();
        $c->addUsingOperator('foo1', 'bar1');
        $c->_or();
        $c->addUsingOperator('foo2', 'bar2');
        $c->addUsingOperator('foo3', 'bar3');
        $expected = 'SELECT  FROM  WHERE (foo1=:p1 OR foo2=:p2) AND foo3=:p3';

        $params = array();
        $result = $c->createSelectSql($params);

        $this->assertEquals($expected, $result);
    }
}
