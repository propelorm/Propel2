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
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author     François Zaninotto
 * @author     heltem <heltem@o2php.com>
 * @package    propel.generator.behavior.nestedset
 */
class SortableBehaviorObjectBuilderModifier
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
	
	/**
	 * Get the getter of the column of the behavior
	 *
	 * @return string The related getter, e.g. 'getRank'
	 */
	protected function getColumnGetter()
	{
		return 'get' . $this->behavior->getColumnForParameter('rank_column')->getPhpName();
	}

	/**
	 * Get the setter of the column of the behavior
	 *
	 * @return string The related setter, e.g. 'setRank'
	 */
	protected function getColumnSetter()
	{
		return 'set' . $this->behavior->getColumnForParameter('rank_column')->getPhpName();
	}

	/**
	 * Add code in ObjectBuilder::preSave
	 *
	 * @return string The code to put at the hook
	 */
	public function preInsert($builder)
	{
		$this->setBuilder($builder);
		return "
if (!\$this->isColumnModified({$this->peerClassname}::RANK_COL)) {
	\$this->{$this->getColumnSetter()}({$this->peerClassname}::getMaxPosition(\$con) + 1);
}
";
	}
	

	/**
	 * Add code in ObjectBuilder::preDelete
	 *
	 * @return string The code to put at the hook
	 */
	public function preDelete($builder)
	{
		$this->setBuilder($builder);
		return "
{$this->peerClassname}::shiftRank(-1, \$this->{$this->getColumnGetter()}() + 1, \$con);
{$this->peerClassname}::clearInstancePool();
";
	}

	/**
	 * Class methods
	 *
	 * @return string
	 */
	public function objectMethods($builder)
	{
		$this->setBuilder($builder);
		$script = '';
		if ($this->getParameter('rank_column') != 'rank') {
			$this->addRankAccessors($script);
		}
		$this->addIsFirst($script);
		$this->addIsLast($script);
		$this->addGetNext($script);
		$this->addGetPrevious($script);
		$this->addInsertAtRank($script);
		$this->addInsertAtBottom($script);
		$this->addInsertAtTop($script);
		$this->addMoveToPosition($script);
		$this->addSwapWith($script);
		$this->addMoveUp($script);
		$this->addMoveDown($script);
		$this->addMoveToTop($script);
		$this->addMoveToBottom($script);
		
		return $script;
	}

	/**
	 * Get the wraps for getter/setter, if the column has not the default name
	 *
	 * @return string
	 */
	protected function addRankAccessors(&$script)
	{
    $script .= "
/**
 * Wrap the getter for position value
 *
 * @return    int
 */
public function getRank()
{
	return \$this->{$this->getColumnAttribute('rank_column')};
}

/**
 * Wrap the setter for position value
 *
 * @param     int
 * @return    {$this->objectClassname}
 */
public function setRank(\$v)
{
	return \$this->{$this->getColumnSetter()}(\$v);
}
";
	}

	protected function addIsFirst(&$script)
	{
		$script .= "
/**
 * Check if the object is first in the list, i.e. if it has 1 for position
 *
 * @return    boolean
 */
public function isFirst()
{
	return \$this->{$this->getColumnGetter()}() == 1;
}
";
	}

	protected function addIsLast(&$script)
	{
		$script .= "
/**
 * Check if the object is last in the list, i.e. if its position is the highest position
 * @return    boolean
 */
public function isLast()
{
	return \$this->{$this->getColumnGetter()}() == {$this->peerClassname}::getMaxPosition();
}
";
	}

	protected function addGetNext(&$script)
	{
		$script .= "
/**
 * Get the next item in the list, i.e. the one for which position is immediately higher
 *
 * @return    {$this->objectClassname}
 */
public function getNext()
{
	return {$this->peerClassname}::retrieveByPosition(\$this->{$this->getColumnGetter()}() + 1);
}
";
	}

	protected function addGetPrevious(&$script)
	{
		$script .= "
/**
 * Get the previous item in the list, i.e. the one for which position is immediately lower
 *
 * @return    {$this->objectClassname}
 */
public function getPrevious()
{
	return {$this->peerClassname}::retrieveByPosition(\$this->{$this->getColumnGetter()}() - 1);
}
";
	}

	protected function addInsertAtRank(&$script)
	{
		$peerClassname = $this->peerClassname;
		$script .= "
/**
 * Insert at specified position
 *
 * @param     integer    \$rank position value
 * @param     PropelPDO  \$con      optional connection
 * @return    integer    the new position
 * @throws    PropelException
 */
public function insertAtRank(\$rank, PropelPDO \$con = null)
{
	if (\$rank < 1 || \$rank > $peerClassname::getMaxPosition()) {
		throw new PropelException('Invalid rank ' . \$rank);
	}
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	\$con->beginTransaction();
	try {
		// shift the objects with a position higher than the given position
		{$this->peerClassname}::shiftRank(1, \$rank, \$con);

		// move the object in the list, at the given position
		\$this->{$this->getColumnSetter()}(\$rank);
		\$this->save(\$con);

		\$con->commit();
		return \$position;
	} catch (PropelException \$e) {
		\$con->rollback();
		\throw $e;
	}
}
";
	}

	protected function addInsertAtBottom(&$script)
	{
		$script .= "
/**
 * insert in the last position
 *
 * @param PropelPDO \$con optional connection
 */
public function insertAtBottom(PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	\$con->beginTransaction();
	try {
		\$this->{$this->getColumnSetter()}({$this->peerClassname}::getMaxPosition(\$con) + 1);
		\$this->save(\$con);
		\$con->commit();
	} catch (PropelException \$e) {
		\$con->rollback();
		\throw $e;
	}
}
";
	}

	protected function addInsertAtTop(&$script)
	{
		$script .= "
/**
 * insert in the first position
 *
 * @param PropelPDO \$con optional connection
 */
public function insertAtTop(PropelPDO \$con = null)
{
	\$this->insertAtRank(1, \$con);
}
";
	}

	protected function addMoveToPosition(&$script)
	{
		$script .= "
/**
 * Move the object to a new position, and shifts the position
 * Of the objects inbetween the old and new position accordingly
 *
 * @param	integer	 \$newPosition position value
 * @param	PropelPDO \$con				 optional connection
 * @return integer								the old object's position
 * @throws PropelException
 */
public function moveToPosition(\$newPosition, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}

	\$oldPosition = \$this->{$this->getColumnGetter()}();
	if (\$oldPosition == \$newPosition) {
		return \$oldPosition;
	}

	try {
		\$con->beginTransaction();

		// move the object away
		\$this->{$this->getColumnSetter()}({$this->peerClassname}::getMaxPosition() + 1);
		\$this->save();

		// shift the objects between the old and the new position
		\$query = sprintf('UPDATE %s SET %s = %s %s 1 WHERE %s BETWEEN ? AND ?',
			'{$this->table->getName()}',
			'{$this->behavior->getColumnForParameter('rank_column')->getName()}',
			'{$this->behavior->getColumnForParameter('rank_column')->getName()}',
			(\$oldPosition < \$newPosition) ? '-' : '+',
			'{$this->behavior->getColumnForParameter('rank_column')->getName()}'
		);
		\$stmt = \$con->prepare(\$query);
		\$stmt->bindParam(1, min(\$oldPosition, \$newPosition));
		\$stmt->bindParam(2, max(\$oldPosition, \$newPosition));
		\$stmt->execute();

		// move the object back in
		\$this->{$this->getColumnSetter()}(\$newPosition);
		\$this->save();

		\$con->commit();
		return \$oldPosition;
	} catch (Exception \$e) {
		\$con->rollback();
		throw \$e;
	}
}
";
	}

	protected function addSwapWith(&$script)
	{
		$script .= "
/**
 * Exchange the position of the object with the one passed as argument
 *
 * @param	{$this->objectClassname} \$object
 * @param	PropelPDO \$con optional connection
 * @return array					the swapped ranks
 * @throws Exception if the database cannot execute the two updates
 */
public function swapWith({$this->objectClassname} \$object, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}

	try {
		\$con->beginTransaction();
		\$oldRank = \$this->{$this->getColumnGetter()}();
		\$newRank = \$object->{$this->getColumnGetter()}();
		\$this->{$this->getColumnSetter()}(\$newRank);
		\$this->save();
		\$object{$this->getColumnSetter()}($oldRank);
		\$object->save();
		\$con->commit();

		return array(\$oldRank, \$newRank);
	} catch (Exception \$e) {
		\$con->rollback();
		throw \$e;
	}
}
";
	}

	protected function addMoveUp(&$script)
	{
		$script .= "
/**
 * Move the object higher in the list, i.e. exchanges its position with the one of the previous object
 *
 * @return array the swapped ranks, or false if not swapped
 */
public function moveUp()
{
	return \$this->isFirst() ? false : \$this->swapWith(\$this->getPrevious());
}
";
	}

	protected function addMoveDown(&$script)
	{
		$script .= "
/**
 * Move the object higher in the list, i.e. exchanges its position with the one of the next object
 *
 * @return array the swapped ranks, or false if not swapped
 */
public function moveDown()
{
	return \$this->isLast() ? false : \$this->swapWith(\$this->getNext());
}
";
	}

	protected function addMoveToTop(&$script)
	{
		$script .= "
/**
 * Move the object to the top of the list
 *
 * @return integer the old object's position
 */
public function moveToTop()
{
	return \$this->moveToPosition(1);
}
";
	}

	protected function addMoveToBottom(&$script)
	{
		$script .= "
/**
 * Move the object to the bottom of the list
 *
 * @return integer the old object's position
 */
public function moveToBottom()
{
	return \$this->moveToPosition({$this->peerClassname}::getMaxPosition());
}
";
	}
}