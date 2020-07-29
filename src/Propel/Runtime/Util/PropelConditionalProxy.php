<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Util;

use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Proxy for conditional statements in a fluid interface.
 * This class replaces another class for wrong statements,
 * and silently catches all calls to non-conditional method calls
 *
 * @example
 * <code>
 * $c->_if(true) // returns $c
 *     ->doStuff() // executed
 *   ->_else() // returns a PropelConditionalProxy instance
 *     ->doOtherStuff() // not executed
 *   ->_endif(); // returns $c
 * $c->_if(false) // returns a PropelConditionalProxy instance
 *     ->doStuff() // not executed
 *   ->_else() // returns $c
 *     ->doOtherStuff() // executed
 *   ->_endif(); // returns $c
 * @see Criteria
 *
 * @author Francois Zaninotto
 */
class PropelConditionalProxy
{
    /**
     * @var \Propel\Runtime\ActiveQuery\Criteria
     */
    protected $criteria;

    /**
     * @var \Propel\Runtime\Util\PropelConditionalProxy|null
     */
    protected $parent;

    /**
     * @var bool
     */
    protected $state;

    /**
     * @var bool
     */
    protected $wasTrue;

    /**
     * @var bool
     */
    protected $parentState;

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param mixed $cond
     * @param \Propel\Runtime\Util\PropelConditionalProxy|self|null $proxy
     */
    public function __construct(Criteria $criteria, $cond, ?self $proxy = null)
    {
        $this->criteria = $criteria;
        $this->wasTrue = false;
        $this->setConditionalState($cond);
        $this->parent = $proxy;

        if ($proxy === null) {
            $this->parentState = true;
        } else {
            $this->parentState = $proxy->getConditionalState();
        }
    }

    /**
     * Returns a new level PropelConditionalProxy instance.
     * Allows for conditional statements in a fluid interface.
     *
     * @param bool $cond
     *
     * @return $this|\Propel\Runtime\ActiveQuery\Criteria
     */
    public function _if($cond)
    {
        return $this->criteria->_if($cond);
    }

    /**
     * Allows for conditional statements in a fluid interface.
     *
     * @param bool $cond ignored
     *
     * @return $this|\Propel\Runtime\ActiveQuery\Criteria
     */
    public function _elseif($cond)
    {
        return $this->setConditionalState(!$this->wasTrue && $cond);
    }

    /**
     * Allows for conditional statements in a fluid interface.
     *
     * @return $this|\Propel\Runtime\ActiveQuery\Criteria
     */
    public function _else()
    {
        return $this->setConditionalState(!$this->state && !$this->wasTrue);
    }

    /**
     * Returns the parent object
     * Allows for conditional statements in a fluid interface.
     *
     * @return $this|\Propel\Runtime\ActiveQuery\Criteria
     */
    public function _endif()
    {
        return $this->criteria->_endif();
    }

    /**
     * return the current conditional status
     *
     * @return bool
     */
    protected function getConditionalState()
    {
        return $this->state && $this->parentState;
    }

    /**
     * @param mixed $cond
     *
     * @return $this|\Propel\Runtime\ActiveQuery\Criteria
     */
    protected function setConditionalState($cond)
    {
        $this->state = (bool)$cond;
        $this->wasTrue = $this->wasTrue || $this->state;

        return $this->getCriteriaOrProxy();
    }

    /**
     * @return self|null
     */
    public function getParentProxy()
    {
        return $this->parent;
    }

    /**
     * @return $this|\Propel\Runtime\ActiveQuery\Criteria
     */
    public function getCriteriaOrProxy()
    {
        if ($this->state && $this->parentState) {
            return $this->criteria;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        return $this;
    }
}
