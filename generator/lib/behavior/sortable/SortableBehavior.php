<?php

/*
 *	$Id: SortableBehavior.php 1357 2009-12-11 21:22:02Z francois $
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

require_once dirname(__FILE__) . '/SortableBehaviorObjectBuilderModifier.php';
require_once dirname(__FILE__) . '/SortableBehaviorQueryBuilderModifier.php';
require_once dirname(__FILE__) . '/SortableBehaviorPeerBuilderModifier.php';


/**
 * Gives a model class the ability to be ordered
 * Uses one additional column storing the rank
 *
 * @author      Massimiliano Arione
 * @version     $Revision$
 * @package     propel.generator.behavior.sortable
 */
class SortableBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'rank_column'  => 'sortable_rank',
		'use_scope'    => 'false',
		'scope_column' => 'sortable_scope',
	);

	protected $objectBuilderModifier, $queryBuilderModifier, $peerBuilderModifier;

	/**
	 * Add the rank_column to the current table
	 */
	public function modifyTable()
	{
		if (!$this->getTable()->containsColumn($this->getParameter('rank_column'))) {
			$this->getTable()->addColumn(array(
				'name' => $this->getParameter('rank_column'),
				'type' => 'INTEGER'
			));
		}
		if ($this->getParameter('use_scope') == 'true' && 
			 !$this->getTable()->containsColumn($this->getParameter('scope_column'))) {
			$this->getTable()->addColumn(array(
				'name' => $this->getParameter('scope_column'),
				'type' => 'INTEGER'
			));
		}
	}

	public function getObjectBuilderModifier()
	{
		if (is_null($this->objectBuilderModifier)) {
			$this->objectBuilderModifier = new SortableBehaviorObjectBuilderModifier($this);
		}
		return $this->objectBuilderModifier;
	}

	public function getQueryBuilderModifier()
	{
		if (is_null($this->queryBuilderModifier)) {
			$this->queryBuilderModifier = new SortableBehaviorQueryBuilderModifier($this);
		}
		return $this->queryBuilderModifier;
	}
	
	public function getPeerBuilderModifier()
	{
		if (is_null($this->peerBuilderModifier)) {
			$this->peerBuilderModifier = new SortableBehaviorPeerBuilderModifier($this);
		}
		return $this->peerBuilderModifier;
	}
	
	public function useScope()
	{
		return $this->getParameter('use_scope') == 'true';
	}

}
