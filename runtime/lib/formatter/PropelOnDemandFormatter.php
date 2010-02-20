<?php

/*
 *  $Id: PropelObjectFormatter.php 1380 2009-12-28 10:11:46Z francois $
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
 * format() returns a PropelOnDemandCollection that hydrates objects as the use iterates on the collection
 * This formatter consumes less memory than the PropelObjectFormatter, but doesn't use Instance Pool
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
class PropelOnDemandFormatter extends PropelObjectFormatter
{
	protected $collectionName = 'PropelOnDemandCollection';
	
	public function format(PDOStatement $stmt)
	{
		$this->checkCriteria();
		if ($this->getCriteria()->isWithOneToMany()) {
			throw new PropelException('PropelOnDemandFormatter cannot hydrate related objects using a one-to-many relationship. Try removing with() from your query.');
		}
		$class = $this->collectionName;
		$collection = new $class();
		$collection->setModel($this->getCriteria()->getModelName());
		$collection->setFormatter($this);
		$collection->setStatement($stmt);
		
		return $collection;
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
		$obj = $this->getSingleObjectFromRow($row, $class, $col);
		
		foreach ($this->getCriteria()->getWith() as $join) {
			$startObject = $join->getObjectToRelate($obj);
			$tableMap = $join->getTableMap(); 
			$class = $tableMap->isSingleTableInheritance() ? call_user_func(array($tableMap->getPeerClassname(), 'getOMClass'), $row, $col, false) : $tableMap->getClassname(); 
			$endObject = $this->getSingleObjectFromRow($row, $class, $col);
			// as we may be in a left join, the endObject may be empty
			// in which case it should not be related to the previous object
			if ($endObject->isPrimaryKeyNull()) {
				continue;
			}
			$method = 'set' . $join->getRelationMap()->getName();
			$startObject->$method($endObject);
		}
		foreach ($this->getCriteria()->getAsColumns() as $alias => $clause) {
			$obj->setVirtualColumn($alias, $row[$col]);
			$col++;
		}
		return $obj;
	}
	
}