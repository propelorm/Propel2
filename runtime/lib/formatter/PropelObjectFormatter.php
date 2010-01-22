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
 * Object formatter for Propel query
 * format() returns a PropelObjectCollection of Propel model objects
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
class PropelObjectFormatter extends PropelFormatter
{
	protected $collectionName = 'PropelObjectCollection';
	
	public function format(PDOStatement $stmt)
	{
		$this->checkCriteria();
		$class = $this->collectionName;
		if(class_exists($class)) {
			$collection = new $class();
			$collection->setModel($this->getCriteria()->getModelName());
		} else {
			$collection = array();
		}
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$collection[] = $this->getAllObjectsFromRow($row);
		}
		$stmt->closeCursor();
		
		return $collection;
	}
	
	public function formatOne(PDOStatement $stmt)
	{
		$this->checkCriteria();
		if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$result = $this->getAllObjectsFromRow($row);
		} else {
			$result = null;
		}
		$stmt->closeCursor();
		
		return $result;
	}
	
	public function isObjectFormatter()
	{
		return true;
	}
	
	/**
	 * Hydrates a series of objects from a result row
	 * The first object to hydrate is the model of the Criteria
	 * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
	 *
	 *  @param    array  $row associative array indexed by column number,
	 *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 *
	 * @return    BaseObject
	 */
	public function getAllObjectsFromRow($row)
	{
		$col = 0;
		$tableMap = $this->getCriteria()->getTableMap();
		$class = $tableMap->isSingleTableInheritance() ? call_user_func(array($tableMap->getPeerClassname(), 'getOMClass'), $row, $col, false) : $this->class;

		$obj = $this->getSingleObjectFromRow($row, $class, $this->peer, $col);
		foreach ($this->getCriteria()->getWith() as $join) {
			$startObject = $join->getObjectToRelate($obj);
			$tableMap = $join->getTableMap();
			$class = $tableMap->isSingleTableInheritance() ? call_user_func(array($tableMap->getPeerClassname(), 'getOMClass'), $row, $col, false) : $tableMap->getClassname();
			$endObject = $this->getSingleObjectFromRow($row, $class, $tableMap->getPeerClassname(), $col);
			// as we may be in a left join, the endObject may be empty
			// in which case it should not be related to the previous object
			if ($endObject->isPrimaryKeyNull()) {
				continue;
			}
			$method = 'set' . $join->getRelationMap()->getName();
			$startObject->$method($endObject);
		}
		return $obj;
	}
	
	/**
	 * Gets a Propel object hydrated from a selection of columns in statement row
	 *
	 * @param     array  $row associative array indexed by column number,
	 *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 * @param     string $class The classname of the object to create
	 * @param     string $peer The peer classname of the object to create
	 * @param     int    $col The start column for the hydration - modified by the method
	 *
	 * @return    BaseObject
	 */
	public function getSingleObjectFromRow($row, $class, $peer, &$col = 0)
	{
		$key = call_user_func(array($peer, 'getPrimaryKeyHashFromRow'), $row, $col);
		$obj = call_user_func(array($peer, 'getInstanceFromPool'), $key);
		if (null === $obj) {
			$obj = new $class();
			$col = $obj->hydrate($row, $col);
			call_user_func(array($peer, 'addInstanceToPool'), $obj, $key);
		} else {
			$col = $col + constant($peer . '::NUM_COLUMNS');
		}
		return $obj;
	}

}