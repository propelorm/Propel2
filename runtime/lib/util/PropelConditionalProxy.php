<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Proxy for conditional statements in a fluid interface.
 * This class replaces another class for wrong statements,
 * and silently catches all calls to non-conditional method calls
 *
 * @example
 * <code>
 * $c->_if(true)        // returns $c
 *     ->doStuff()      // executed
 *   ->_else()          // returns a PropelConditionalProxy instance
 *     ->doOtherStuff() // not executed
 *   ->_endif();        // returns $c
 * $c->_if(false)       // returns a PropelConditionalProxy instance
 *     ->doStuff()      // not executed
 *   ->_else()          // returns $c
 *     ->doOtherStuff() // executed
 *   ->_endif();        // returns $c
 * @see Criteria
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.util
 */
class PropelConditionalProxy
{
	protected $mainObject;
	
	public function __construct($mainObject)
	{
		$this->mainObject = $mainObject;
	}
	
	public function _if($cond)
	{
		return $this->mainObject->_if($cond);
	}
	
	public function _elseif($cond)
	{
		return $this->mainObject->_elseif($cond);
	}
	
	public function _else()
	{
		return $this->mainObject->_else();
	}
	
	public function _endif()
	{
		return $this->mainObject->_endif();
	}
	
	public function __call($name, $arguments)
	{
		return $this;
	}
}