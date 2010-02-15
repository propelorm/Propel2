<?php
/*
 *  $Id: ModelJoin.php 1347 2009-12-03 21:06:36Z francois $
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
 * A ModelJoin is a Join object tied to a table object
 *
 * @author     Francois Zaninotto (Propel)
 * @package    propel.runtime.query
 */
class ModelJoin extends Join
{
	protected $tableMap;
	protected $relationMap;
	protected $previousJoin;
	protected $relationAlias;
	
	/**
	 * Sets the right tableMap for this join
	 * 
	 * @param TableMap $tableMap The table map to use
	 * 
	 * @return ModelJoin The current join object, for fluid interface
	 */
	public function setTableMap(TableMap $tableMap)
	{
		$this->tableMap = $tableMap;
		
		return $this;
	}

	/**
	 * Gets the right tableMap for this join
	 * 
	 * @return TableMap The table map
	 */
	public function getTableMap()
	{
		return $this->tableMap;
	}

	public function setRelationMap(RelationMap $relationMap, $leftTableAlias = null, $rightTableAlias = null)
	{
		$leftCols = $relationMap->getLeftColumns();
		$rightCols = $relationMap->getRightColumns();
		$nbColumns = $relationMap->countColumnMappings();
		for ($i=0; $i < $nbColumns; $i++) {
			$leftColName  = ($leftTableAlias  ? $leftTableAlias  : $leftCols[$i]->getTableName()) . '.' . $leftCols[$i]->getName();
			$rightColName = ($rightTableAlias ? $rightTableAlias : $rightCols[$i]->getTableName()) . '.' . $rightCols[$i]->getName();
			$this->addCondition($leftColName, $rightColName, Criteria::EQUAL);
		}
		$this->relationMap = $relationMap;
		if (null !== $rightTableAlias) {
			$this->setRelationAlias($rightTableAlias);
		}
		
		return $this;
	}
	
	public function getRelationMap()
	{
		return $this->relationMap;
	}
	
	public function setPreviousJoin(ModelJoin $join)
	{
		$this->previousJoin = $join;
		
		return $this;
	}
	
	public function getPreviousJoin()
	{
		return $this->previousJoin;
	}
	
	public function isPrimary()
	{
		return null === $this->previousJoin;
	}

	public function setRelationAlias($relationAlias)
	{
		$this->relationAlias = $relationAlias;
		
		return $this;
	}
	
	public function getRelationAlias()
	{
		return $this->relationAlias;
	}
	
	public function hasRelationAlias()
	{
		return null !== $this->relationAlias;
	}

	public function getObjectToRelate($startObject)
	{
		if($this->isPrimary()) {
			return $startObject;
		} else {
			$previousJoin = $this->getPreviousJoin();
			$previousObject = $previousJoin->getObjectToRelate($startObject);
			$method = 'get' . $previousJoin->getRelationMap()->getName();
			return $previousObject->$method();
		}
	}

	public function equals($join)
	{
		return parent::equals($join)
			&& $this->tableMap == $join->getTableMap()
			&& $this->relationMap == $join->getRelationMap()
			&& $this->previousJoin == $join->getPreviousJoin()
			&& $this->relationAlias == $join->getRelationAlias();
	}
	
	public function __toString()
	{
		return parent::toString()
			. ' tableMap: ' . ($this->tableMap ? get_class($this->tableMap) : 'null')
			. ' relationMap: ' . $this->relationMap->getName()
			. ' previousJoin: ' . ($this->previousJoin ? '(' . $this->previousJoin . ')' : 'null')
			. ' relationAlias: ' . $this->relationAlias;
	}
}
 