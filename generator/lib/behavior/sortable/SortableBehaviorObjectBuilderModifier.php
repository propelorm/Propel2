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
{$this->peerClassname}::shiftRank(-1, \$this->{$this->getColumnGetter()}() + 1, null, \$con);
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
		$this->addMoveToRank($script);
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
 * Wrap the getter for rank value
 *
 * @return    int
 */
public function getRank()
{
	return \$this->{$this->getColumnAttribute('rank_column')};
}

/**
 * Wrap the setter for rank value
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
 * Check if the object is first in the list, i.e. if it has 1 for rank
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
 * Check if the object is last in the list, i.e. if its rank is the highest rank
 *
 * @param     PropelPDO  \$con      optional connection
 *
 * @return    boolean
 */
public function isLast(PropelPDO \$con = null)
{
	return \$this->{$this->getColumnGetter()}() == {$this->peerClassname}::getMaxPosition(\$con);
}
";
	}

	protected function addGetNext(&$script)
	{
		$script .= "
/**
 * Get the next item in the list, i.e. the one for which rank is immediately higher
 *
 * @param     PropelPDO  \$con      optional connection
 *
 * @return    {$this->objectClassname}
 */
public function getNext(PropelPDO \$con = null)
{
	return {$this->peerClassname}::retrieveByPosition(\$this->{$this->getColumnGetter()}() + 1, \$con);
}
";
	}

	protected function addGetPrevious(&$script)
	{
		$script .= "
/**
 * Get the previous item in the list, i.e. the one for which rank is immediately lower
 *
 * @param     PropelPDO  \$con      optional connection
 *
 * @return    {$this->objectClassname}
 */
public function getPrevious(PropelPDO \$con = null)
{
	return {$this->peerClassname}::retrieveByPosition(\$this->{$this->getColumnGetter()}() - 1, \$con);
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
 *
 * @return    integer    the new position
 *
 * @throws    PropelException
 */
public function insertAtRank(\$rank, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	if (\$rank < 1 || \$rank > $peerClassname::getMaxPosition(\$con)) {
		throw new PropelException('Invalid rank ' . \$rank);
	}
	\$con->beginTransaction();
	try {
		// shift the objects with a position higher than the given position
		{$this->peerClassname}::shiftRank(1, \$rank, null, \$con);

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
 * Insert in the last position
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
 * Insert in the first position
 *
 * @param PropelPDO \$con optional connection
 */
public function insertAtTop(PropelPDO \$con = null)
{
	\$this->insertAtRank(1, \$con);
}
";
	}

	protected function addMoveToRank(&$script)
	{
		$peerClassname = $this->peerClassname;
		$script .= "
/**
 * Move the object to a new rank, and shifts the rank
 * Of the objects inbetween the old and new rank accordingly
 *
 * @param     integer   \$newPosition position value
 * @param     PropelPDO \$con optional connection
 *
 * @return    integer the old object's position
 *
 * @throws    PropelException
 */
public function moveToRank(\$newRank, PropelPDO \$con = null)
{
	if (\$this->isNew()) {
		throw new PropelException('New objects cannot be moved. Please use insertAtRank() instead');
	}
	if (\$con === null) {
		\$con = Propel::getConnection($peerClassname::DATABASE_NAME);
	}
	if (\$newRank < 1 || \$newRank > $peerClassname::getMaxPosition(\$con)) {
		throw new PropelException('Invalid rank ' . \$newRank);
	}

	\$oldRank = \$this->{$this->getColumnGetter()}();
	if (\$oldRank == \$newRank) {
		return \$oldRank;
	}
	
	\$con->beginTransaction();
	try {
		// shift the objects between the old and the new position
		\$delta = (\$oldRank < \$newRank) ? -1 : 1;
		$peerClassname::shiftRank(\$delta, min(\$oldRank, \$newRank), max(\$oldRank, \$newRank), \$con);

		// move the object to its new position
		\$this->{$this->getColumnSetter()}(\$newRank);
		\$this->save(\$con);

		\$con->commit();
		return \$oldRank;
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
 * Exchange the rank of the object with the one passed as argument, and saves both objects
 *
 * @param     {$this->objectClassname} \$object
 * @param     PropelPDO \$con optional connection
 *
 * @return    array the swapped ranks
 *
 * @throws Exception if the database cannot execute the two updates
 */
public function swapWith({$this->objectClassname} \$object, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	\$con->beginTransaction();
	try {
		\$oldRank = \$this->{$this->getColumnGetter()}();
		\$newRank = \$object->{$this->getColumnGetter()}();
		\$this->{$this->getColumnSetter()}(\$newRank);
		\$this->save(\$con);
		\$object->{$this->getColumnSetter()}(\$oldRank);
		\$object->save(\$con);
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
 * Move the object higher in the list, i.e. exchanges its rank with the one of the previous object
 *
 * @param     PropelPDO \$con optional connection
 *
 * @return array the swapped ranks, or false if not swapped
 */
public function moveUp(PropelPDO \$con = null)
{
	if (\$this->isFirst()) {
		return false;
	}
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	\$con->beginTransaction();
	try {
		\$prev = \$this->getPrevious(\$con);
		\$res = \$this->swapWith(\$prev, \$con);
		\$con->commit();
		
		return \$res;
	} catch (Exception \$e) {
		\$con->rollback();
		throw \$e;
	}
}
";
	}

	protected function addMoveDown(&$script)
	{
		$script .= "
/**
 * Move the object higher in the list, i.e. exchanges its rank with the one of the next object
 *
 * @param     PropelPDO \$con optional connection
 *
 * @return array the swapped ranks, or false if not swapped
 */
public function moveDown(PropelPDO \$con = null)
{
	if (\$this->isLast(\$con)) {
		return false;
	}
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	\$con->beginTransaction();
	try {
		\$next = \$this->getNext(\$con);
		\$res = \$this->swapWith(\$next, \$con);
		\$con->commit();
		
		return \$res;
	} catch (Exception \$e) {
		\$con->rollback();
		throw \$e;
	}
}
";
	}

	protected function addMoveToTop(&$script)
	{
		$script .= "
/**
 * Move the object to the top of the list
 *
 * @param     PropelPDO \$con optional connection
 *
 * @return integer the old object's rank
 */
public function moveToTop(PropelPDO \$con = null)
{
	if (\$this->isFirst()) {
		return false;
	}
	return \$this->moveToRank(1, \$con);
}
";
	}

	protected function addMoveToBottom(&$script)
	{
		$script .= "
/**
 * Move the object to the bottom of the list
 *
 * @param     PropelPDO \$con optional connection
 *
 * @return integer the old object's rank
 */
public function moveToBottom(PropelPDO \$con = null)
{
	if (\$this->isLast(\$con)) {
		return false;
	}
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	\$con->beginTransaction();
	try {
		\$bottom = {$this->peerClassname}::getMaxPosition(\$con);
		\$res = \$this->moveToRank(\$bottom, \$con);
		\$con->commit();
		
		return \$res;
	} catch (Exception \$e) {
		\$con->rollback();
		throw \$e;
	}
}
";
	}
}