<?php

/*
 *  $Id$
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
 * Array formatter for Propel query
 * format() returns a PropelArrayCollection of associative arrays
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
class PropelArrayFormatter extends PropelFormatter
{
	protected $currentObjects = array();
	
	public function format(PDOStatement $stmt)
	{
		$this->checkCriteria();
		$collection = new PropelArrayCollection();
		$collection->setModel($this->getCriteria()->getModelName());
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$collection[] = $this->getStructuredArrayFromRow($row);
		}
		$this->currentObjects = array();
		$stmt->closeCursor();
		
		return $collection;
	}

	public function formatOne(PDOStatement $stmt)
	{
		$this->checkCriteria();
		if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$result = $this->getStructuredArrayFromRow($row);
		} else {
			$result = null;
		}
		$this->currentObjects = array();
		$stmt->closeCursor();
		return $result;
	}

	public function isObjectFormatter()
	{
		return false;
	}
	
	/**
	 * Gets the current object for the class
	 * To save memory, we don't create a new object for each row
	 * but we keep hydrating a single object per class
	 * 
	 * @param     string $class Propel model object class
	 * 
	 * @return    BaseObject
	 */
	protected function getCurrentObject($class)
	{
		if(!array_key_exists($class, $this->currentObjects)) {
			$this->currentObjects[$class] = new $class();
		}
		return $this->currentObjects[$class];
	}

	/**
	 * Hydrates a series of objects from a result row
	 * The first object to hydrate is the model of the Criteria
	 * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
	 *
	 *  @param    array  $row associative array indexed by column number,
	 *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 *
	 * @return    Array
	 */
	public function getStructuredArrayFromRow($row)
	{
		$col = 0;
		$mainObjectArray = $this->getSingleObjectFromRow($row, $this->class, $col)->toArray();
		foreach ($this->getCriteria()->getWith() as $join) {
			$secondaryObject = $this->getSingleObjectFromRow($row, $join->getTableMap()->getClassname(), $col);
			if ($secondaryObject->isPrimaryKeyNull()) {
				$secondaryObjectArray = array();
			} else {
				$secondaryObjectArray = $secondaryObject->toArray();
			}
			$arrayToAugment = &$mainObjectArray;
			if (!$join->isPrimary()) {
				$prevJoin = $join;
				while($prevJoin = $prevJoin->getPreviousJoin()) {
					$arrayToAugment = &$arrayToAugment[$prevJoin->getRelationMap()->getName()];
				}
			}
			$arrayToAugment[$join->getRelationMap()->getName()] = $secondaryObjectArray;
		}
		
		return $mainObjectArray;
	}
	
		/**
	 * Gets a Propel object hydrated from a selection of columns in statement row
	 *
	 * @param     array  $row associative array indexed by column number,
	 *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 * @param     string $class The classname of the object to create
	 * @param     int    $col The start column for the hydration (modified)
	 *
	 * @return    BaseObject
	 */
	public function getSingleObjectFromRow($row, $class, &$col = 0)
	{
		$obj = $this->getCurrentObject($class);
		$col = $obj->hydrate($row, $col);
		
		return $obj;
	}

}