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
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author     François Zaninotto
 * @author     heltem <heltem@o2php.com>
 * @package    propel.engine.behavior.nestedset
 */
class NestedSetBehaviorPeerBuilderModifier
{
	protected $behavior, $table;
	
	public function __construct($behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}
	
	protected function getParameter($key)
	{
		return $this->behavior->getParameter($key);
	}
	
	protected function getColumn($name)
	{
		return $this->behavior->getColumnForParameter($name);
	}
	
	protected function getColumnAttribute($name)
	{
		return strtolower($this->getColumn($name)->getName());
	}
	
	protected function getColumnConstant($name)
	{
		return strtoupper($this->getColumn($name)->getName());
	}

	protected function getColumnPhpName($name)
	{
		return $this->getColumn($name)->getPhpName();
	}
	
	public function staticAttributes($builder)
	{
		$tableName = $this->table->getName();

		$script = "
/**
 * Left column for the set
 */
const LEFT_COL = '" . $builder->prefixTablename($tableName) . '.' . $this->getColumnConstant('left_column') . "';

/**
 * Right column for the set
 */
const RIGHT_COL = '" . $builder->prefixTablename($tableName) . '.' . $this->getColumnConstant('right_column') . "';
";
	
		if ($this->behavior->useScope()) {
			$script .= 	"
/**
 * Scope column for the set
 */
const SCOPE_COL = '" . $builder->prefixTablename($tableName) . '.' . $this->getColumnConstant('scope_column') . "';
";
		}
		
		return $script;
	}
	
	public function staticMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		
		$this->addRetrieveRoot($script);
		$this->addIsValid($script);
		$this->addShiftRLValues($script);
		$this->addUpdateLoadedNodes($script);
		$this->addMakeRoomForLeaf($script);
		
		return $script;
	}

	protected function addRetrieveRoot(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Returns the root node for a given scope
 *";
 		if($useScope) {
 			$script .= "
 * @param      int \$scope		Scope to determine which root node to return";
 		}
 		$script .= "
 * @param      PropelPDO \$con	Connection to use.
 * @return     $objectClassname			Propel object for root node
 */
public static function retrieveRoot(" . ($useScope ? "\$scope = null, " : "") . "PropelPDO \$con = null)
{
	\$c = new Criteria($peerClassname::DATABASE_NAME);
	\$c->add(self::LEFT_COL, 1, Criteria::EQUAL);";
		if($useScope) {
			$script .= "
	\$c->add(self::SCOPE_COL, \$scope, Criteria::EQUAL);";
		}
		$script .= "

	return $peerClassname::doSelectOne(\$c, \$con);
}
";
	}
	
	protected function addIsValid(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$script .= "
/**
 * Tests if node is valid
 *
 * @param      $objectClassname \$node	Propel object for src node
 * @return     bool
 */
public static function isValid($objectClassname \$node = null)
{
	if (is_object(\$node) && \$node->getRightValue() > \$node->getLeftValue()) {
		return true;
	} else {
		return false;
	}
}
";
	}

	protected function addShiftRLValues(&$script)
	{
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Adds '\$delta' to all L and R values that are >= '\$first'. '\$delta' can also be negative.
 *
 * @param      int \$first		First node to be shifted
 * @param      int \$delta		Value to be shifted by, can be negative
 * @param      PropelPDO \$con		Connection to use.";
		if($useScope) {
			$script .= "
 * @param      int \$scope		Scope to use for the shift";
		}
		$script .= "
 */
public static function shiftRLValues(\$first, \$delta, PropelPDO \$con = null" . ($useScope ? ", \$scope = null" : ""). ")
{
	if (\$con === null) {
		\$con = Propel::getConnection($peerClassname::DATABASE_NAME, Propel::CONNECTION_WRITE);
	}

	// Shift left column values
	\$whereCriteria = new Criteria($peerClassname::DATABASE_NAME);";
		if ($useScope) {
			$script .= "
	\$criterion = \$whereCriteria->getNewCriterion(self::LEFT_COL, \$first, Criteria::GREATER_EQUAL);
	\$criterion->addAnd(\$whereCriteria->getNewCriterion(self::SCOPE_COL, \$scope, Criteria::EQUAL));
	\$whereCriteria->add(\$criterion);";
		} else {
			$script .= "
	\$whereCriteria->add(self::LEFT_COL, \$first, Criteria::GREATER_EQUAL);";	
		}
		$script .= "

	\$valuesCriteria = new Criteria($peerClassname::DATABASE_NAME);
	\$valuesCriteria->add(self::LEFT_COL, array('raw' => self::LEFT_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

	{$this->builder->getBasePeerClassname()}::doUpdate(\$whereCriteria, \$valuesCriteria, \$con);

	// Shift right column values
	\$whereCriteria = new Criteria($peerClassname::DATABASE_NAME);";
		if ($useScope) {
			$script .= "
	\$criterion = \$whereCriteria->getNewCriterion(self::RIGHT_COL, \$first, Criteria::GREATER_EQUAL);
	\$criterion->addAnd(\$whereCriteria->getNewCriterion(self::SCOPE_COL, \$scope, Criteria::EQUAL));
	\$whereCriteria->add(\$criterion);";
		} else {
			$script .= "
	\$whereCriteria->add(self::RIGHT_COL, \$first, Criteria::GREATER_EQUAL);";	
		}
		$script .= "

	\$valuesCriteria = new Criteria($peerClassname::DATABASE_NAME);
	\$valuesCriteria->add(self::RIGHT_COL, array('raw' => self::RIGHT_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

	{$this->builder->getBasePeerClassname()}::doUpdate(\$whereCriteria, \$valuesCriteria, \$con);
}
";
	}

	protected function addUpdateLoadedNodes(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();

		$script .= "
/**
 * Reload all already loaded nodes to sync them with updated db
 *
 * @param      PropelPDO \$con		Connection to use.
 */
public static function updateLoadedNodes(PropelPDO \$con = null)
{
	if (Propel::isInstancePoolingEnabled()) {
		\$keys = array();
		foreach (self::\$instances as \$obj) {
			\$keys[] = \$obj->getPrimaryKey();
		}

		if (!empty(\$keys)) {
			// We don't need to alter the object instance pool; we're just modifying these ones
			// already in the pool.
			\$criteria = new Criteria(self::DATABASE_NAME);";
		if (count($this->table->getPrimaryKey()) === 1) {
			$pkey = $this->table->getPrimaryKey();
			$col = array_shift($pkey);
			$script .= "
			\$criteria->add(".$this->builder->getColumnConstant($col).", \$keys, Criteria::IN);
";
		} else {
			$fields = array();
			foreach ($this->table->getPrimaryKey() as $k => $col) {
				$fields[] = $this->builder->getColumnConstant($col);
			};
			$script .= "

			// Loop on each instances in pool
			foreach (\$keys as \$values) {
			  // Create initial Criterion
				\$cton = \$criteria->getNewCriterion(" . $fields[0] . ", \$values[0]);";
			unset($fields[0]);
			foreach ($fields as $k => $col) {
				$script .= "

				// Create next criterion
				\$nextcton = \$criteria->getNewCriterion(" . $col . ", \$values[$k]);
				// And merge it with the first
				\$cton->addAnd(\$nextcton);";
			}
			$script .= "

				// Add final Criterion to Criteria
				\$criteria->addOr(\$cton);
			}";
		}

		$script .= "
			\$stmt = $peerClassname::doSelectStmt(\$criteria, \$con);
			while (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
				\$key = $peerClassname::getPrimaryKeyHashFromRow(\$row, 0);
				if (null !== (\$object = $peerClassname::getInstanceFromPool(\$key))) {";
		$n = 0;
		foreach ($this->table->getColumns() as $col) {
			if ($col->getPhpName() == $this->getColumnPhpName('left_column')) {
				$script .= "
					\$object->setLeftValue(\$row[$n]);";
			} else if ($col->getPhpName() == $this->getColumnPhpName('right_column')) {
				$script .= "
					\$object->setRightValue(\$row[$n]);";
			}
			$n++;
		}
		$script .= "
				}
			}
			\$stmt->closeCursor();
		}
	}
}
";
	}

	protected function addMakeRoomForLeaf(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Update the tree to allow insertion of a leaf at the specified position
 *
 * @param      int \$left	left column value";
 		if ($useScope) {
 			 		$script .= "
 * @param      integer \$scope	scope column value";
 		}
 		$script .= "
 * @param      PropelPDO \$con	Connection to use.
 */
public static function makeRoomForLeaf(\$left" . ($useScope ? ", \$scope" : ""). ", PropelPDO \$con = null)
{	
	// Update database nodes
	$peerClassname::shiftRLValues(\$left, 2, \$con" . ($useScope ? ", \$scope" : "") . ");

	// Update all loaded nodes
	$peerClassname::updateLoadedNodes(\$con);
}
";
	}
}