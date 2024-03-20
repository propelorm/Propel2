<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Util;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Util\PropelConditionalProxy;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for PropelConditionalProxy.
 *
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 */
class PropelConditionalProxyTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testFluidInterface()
    {
        $criteria = new ProxyTestCriteria();
        $p = new TestPropelConditionalProxy($criteria, false);

        $this->assertEquals($p->_elseif(false), $p, '_elseif returns fluid interface');

        $this->assertEquals($p->_elseif(true), $criteria, '_elseif returns fluid interface');

        $this->assertEquals($p->_elseif(false), $p, '_elseif returns fluid interface');

        $this->assertEquals($p->_else(), $p, '_else returns fluid interface');

        $criteria = new ProxyTestCriteria();

        $p = new TestPropelConditionalProxy($criteria, true);

        $this->assertEquals($p->_elseif(true), $p, '_elseif returns fluid interface');

        $this->assertEquals($p->_elseif(false), $p, '_elseif returns fluid interface');

        $this->assertEquals($p->_else(), $p, '_else returns fluid interface');

        $criteria = new ProxyTestCriteria();
        $p = new TestPropelConditionalProxy($criteria, false);

        $this->assertEquals($p->_elseif(false), $p, '_elseif returns fluid interface');

        $this->assertEquals($p->_else(), $criteria, '_else returns fluid interface');
    }

    /**
     * @return void
     */
    public function testHierarchy()
    {
        $criteria = new ProxyTestCriteria();
        $p = new TestPropelConditionalProxy($criteria, true);

        $this->assertEquals($p->getCriteria(), $criteria, 'main object is the given one');

        $this->assertInstanceOf('\Propel\Runtime\Util\PropelConditionalProxy', $p2 = $p->_if(true), '_if returns fluid interface');

        $this->assertEquals($p2->getCriteria(), $criteria, 'main object is the given one, even with nested proxies');

        $this->assertEquals($p2->getParentProxy(), $p, 'nested proxy is respected');

        $p = new PropelConditionalProxy($criteria, true);

        $this->assertEquals($criteria, $p->_if(true), '_if returns fluid interface');
    }
}

class TestPropelConditionalProxy extends PropelConditionalProxy
{
    public function _if($cond)
    {
        return new TestPropelConditionalProxy($this->criteria, $cond, $this);
    }

    public function getParentProxy(): ?self
    {
        return $this->parent;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }
}

class ProxyTestCriteria extends Criteria
{
    protected $test = false;

    public function test()
    {
        $this->test = true;

        return $this;
    }

    public function dummy()
    {
        return $this;
    }

    public function getTest()
    {
        return $this->test;
    }
}
