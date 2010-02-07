<?php

/*
 *  $Id: PropelConditionalProxy.php 1347 2009-12-03 21:06:36Z francois $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
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
	
	public function _if()
	{
		throw new PropelException('_if() statements cannot be nested');
	}
	
	public function _elseif($cond)
	{
		if($cond) {
			return $this->mainObject;
		} else {
			return $this;
		}
	}
	
	public function _else()
	{
		return $this->mainObject;
	}
	
	public function _endif()
	{
		return $this->mainObject;
	}
	
	public function __call($name, $arguments)
	{
		return $this;
	}
}