<?php

/*
 *  $Id: NestedSetBehaviorObjectBuilderModifier.php 1347 2009-12-03 21:06:36Z francois $
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
 * Behavior to add sortable peer methods
 *
 * @author     François Zaninotto
 * @author     heltem <heltem@o2php.com>
 * @package    propel.generator.behavior.sortable
 */
class SortableBehaviorPeerBuilderModifier
{
	protected $behavior, $table, $builder, $objectClassname, $peerClassname;
	
	public function __construct($behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}
	
	protected function getParameter($key)
	{
		return $this->behavior->getParameter($key);
	}
	
	protected function getColumnAttribute($name)
	{
		return strtolower($this->behavior->getColumnForParameter($name)->getName());
	}
	
		protected function getColumnConstant($name)
	{
		return strtoupper($this->behavior->getColumnForParameter($name)->getName());
	}

	protected function getColumnPhpName($name)
	{
		return $this->behavior->getColumnForParameter($name)->getPhpName();
	}
	
	protected function setBuilder($builder)
	{
		$this->builder = $builder;
		$this->objectClassname = $builder->getStubObjectBuilder()->getClassname();
		$this->peerClassname = $builder->getStubPeerBuilder()->getClassname();
	}

	public function staticAttributes($builder)
	{
		$tableName = $this->table->getName();
		$script = "
/**
 * rank column
 */
const RANK_COL = '" . $tableName . '.' . $this->getColumnConstant('rank_column') . "';
";

		if ($this->behavior->useScope()) {
			$script .= 	"
/**
 * Scope column for the set
 */
const SCOPE_COL = '" . $tableName . '.' . $this->getColumnConstant('scope_column') . "';
";
		}
		
		return $script;
	}

	/**
	 * Static methods
	 *
	 * @return string
	 */
	public function staticMethods($builder)
	{
		$this->setBuilder($builder);
		$script = '';
		
		$this->addGetMaxRank($script);
		$this->addRetrieveByRank($script);
		$this->addReorder($script);
		$this->addDoSelectOrderByRank($script);
		if ($this->behavior->useScope()) {
			$this->addRetrieveList($script);
			$this->addCountList($script);
			$this->addDeleteList($script);
		}
		$this->addShiftRank($script);
		
		return $script;
	}
	
	protected function addGetMaxRank(&$script)
	{
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Get the highest rank
 * ";
		if($useScope) {
			$script .= "
 * @param      int \$scope		Scope to determine which suite to consider";
		}
		$script .= "
 * @param     PropelPDO optional connection
 *
 * @return    integer highest position
 */
public static function getMaxRank(" . ($useScope ? "\$scope = null, " : "") . "PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	// shift the objects with a position lower than the one of object
	\$c = new Criteria();
	\$c->addSelectColumn('MAX(' . {$this->peerClassname}::RANK_COL . ')');";
		if ($useScope) {
		$script .= "
	\$c->add({$this->peerClassname}::SCOPE_COL, \$scope, Criteria::EQUAL);";
		}
		$script .= "
	\$stmt = {$this->peerClassname}::doSelectStmt(\$c, \$con);
	
	return \$stmt->fetchColumn();
}
";
	}
	
	protected function addRetrieveByRank(&$script)
	{
		$peerClassname = $this->peerClassname;
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Get an item from the list based on its rank
 *
 * @param     integer   \$rank rank";
		if($useScope) {
			$script .= "
 * @param      int \$scope		Scope to determine which suite to consider";
		}
		$script .= "
 * @param     PropelPDO \$con optional connection
 *
 * @return {$this->objectClassname}
 */
public static function retrieveByRank(\$rank, " . ($useScope ? "\$scope = null, " : "") . "PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection($peerClassname::DATABASE_NAME);
	}

	\$c = new Criteria;
	\$c->add($peerClassname::RANK_COL, \$rank);";
		if($useScope) {
			$script .= "
	\$c->add($peerClassname::SCOPE_COL, \$scope, Criteria::EQUAL);";
		}
		$script .= "
	
	return $peerClassname::doSelectOne(\$c, \$con);
}
";
	}

	protected function addReorder(&$script)
	{
		$peerClassname = $this->peerClassname;
		$columnGetter = 'get' . $this->behavior->getColumnForParameter('rank_column')->getPhpName();
		$columnSetter = 'set' . $this->behavior->getColumnForParameter('rank_column')->getPhpName();
		$script .= "
/**
 * Reorder a set of sortable objects based on a list of id/position
 * Beware that there is no check made on the positions passed
 * So incoherent positions will result in an incoherent list
 *
 * @param     array     \$order id => rank pairs
 * @param     PropelPDO \$con   optional connection
 *
 * @return    boolean true if the reordering took place, false if a database problem prevented it
 */
public static function reorder(array \$order, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection($peerClassname::DATABASE_NAME);
	}
	
	\$con->beginTransaction();
	try {
		\$ids = array_keys(\$order);
		\$objects = $peerClassname::retrieveByPKs(\$ids);
		foreach (\$objects as \$object) {
			\$pk = \$object->getPrimaryKey();
			if (\$object->$columnGetter() != \$order[\$pk]) {
				\$object->$columnSetter(\$order[\$pk]);
				\$object->save(\$con);
			}
		}
		\$con->commit();

		return true;
	} catch (PropelException \$e) {
		\$con->rollback();
		throw \$e;
	}
}
";
	}
	
	protected function addDoSelectOrderByRank(&$script)
	{
		$peerClassname = $this->peerClassname;
		$script .= "
/**
 * Return an array of sortable objects ordered by position
 *
 * @param     Criteria  \$criteria  optional criteria object
 * @param     string    \$order     sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
 * @param     PropelPDO \$con       optional connection
 *
 * @return    array list of sortable objects
 */
public static function doSelectOrderByRank(Criteria \$criteria = null, \$order = Criteria::ASC, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection($peerClassname::DATABASE_NAME);
	}

	if (\$criteria === null) {
		\$criteria = new Criteria();
	} elseif (\$criteria instanceof Criteria) {
		\$criteria = clone \$criteria;
	}

	\$criteria->clearOrderByColumns();

	if (\$order == Criteria::ASC) {
		\$criteria->addAscendingOrderByColumn($peerClassname::RANK_COL);
	} else {
		\$criteria->addDescendingOrderByColumn($peerClassname::RANK_COL);
	}

	return $peerClassname::doSelect(\$criteria, \$con);
}
";
	}
	
	protected function addRetrieveList(&$script)
	{
		$peerClassname = $this->peerClassname;
		$script .= "
/**
 * Return an array of sortable objects in the given scope ordered by position
 *
 * @param     int       \$scope  the scope of the list
 * @param     string    \$order  sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
 * @param     PropelPDO \$con    optional connection
 *
 * @return    array list of sortable objects
 */
public static function retrieveList(\$scope, \$order = Criteria::ASC, PropelPDO \$con = null)
{
	\$c = new Criteria();
	\$c->add($peerClassname::SCOPE_COL, \$scope);
	
	return $peerClassname::doSelectOrderByRank(\$c, \$order, \$con);
}
";
	}

	protected function addCountList(&$script)
	{
		$peerClassname = $this->peerClassname;
		$script .= "
/**
 * Return the number of sortable objects in the given scope
 *
 * @param     int       \$scope  the scope of the list
 * @param     PropelPDO \$con    optional connection
 *
 * @return    array list of sortable objects
 */
public static function countList(\$scope, PropelPDO \$con = null)
{
	\$c = new Criteria();
	\$c->add($peerClassname::SCOPE_COL, \$scope);
	
	return $peerClassname::doCount(\$c, \$con);
}
";
	}

	protected function addDeleteList(&$script)
	{
		$peerClassname = $this->peerClassname;
		$script .= "
/**
 * Deletes the sortable objects in the given scope
 *
 * @param     int       \$scope  the scope of the list
 * @param     PropelPDO \$con    optional connection
 *
 * @return    int number of deleted objects
 */
public static function deleteList(\$scope, PropelPDO \$con = null)
{
	\$c = new Criteria();
	\$c->add($peerClassname::SCOPE_COL, \$scope);
	
	return $peerClassname::doDelete(\$c, \$con);
}
";
	}	
	protected function addShiftRank(&$script)
	{
		$useScope = $this->behavior->useScope();
		$peerClassname = $this->peerClassname;
		$script .= "
/**
 * Adds \$delta to all Rank values that are >= \$first and <= \$last.
 * '\$delta' can also be negative.
 *
 * @param      int \$delta Value to be shifted by, can be negative
 * @param      int \$first First node to be shifted
 * @param      int \$last  Last node to be shifted";
		if($useScope) {
			$script .= "
 * @param      int \$scope Scope to use for the shift";
		}
		$script .= "
 * @param      PropelPDO \$con Connection to use.
 */
public static function shiftRank(\$delta, \$first, \$last = null, " . ($useScope ? "\$scope = null, " : "") . "PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection($peerClassname::DATABASE_NAME, Propel::CONNECTION_WRITE);
	}

	\$whereCriteria = new Criteria($peerClassname::DATABASE_NAME);
	\$criterion = \$whereCriteria->getNewCriterion($peerClassname::RANK_COL, \$first, Criteria::GREATER_EQUAL);
	if (null !== \$last) {
		\$criterion->addAnd(\$whereCriteria->getNewCriterion($peerClassname::RANK_COL, \$last, Criteria::LESS_EQUAL));
	}
	\$whereCriteria->add(\$criterion);";
		if ($useScope) {
			$script .= "
	\$whereCriteria->add($peerClassname::SCOPE_COL, \$scope, Criteria::EQUAL);";
		}
		$script .= "

	\$valuesCriteria = new Criteria($peerClassname::DATABASE_NAME);
	\$valuesCriteria->add($peerClassname::RANK_COL, array('raw' => $peerClassname::RANK_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

	{$this->builder->getPeerBuilder()->getBasePeerClassname()}::doUpdate(\$whereCriteria, \$valuesCriteria, \$con);
	$peerClassname::clearInstancePool();
}
";
	}
}