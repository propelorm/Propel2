<?php

/*
 *	$Id$
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
 * Gives a model class the ability to be ordered
 * Uses one additional column storing the rank
 *
 * @author      Massimiliano Arione
 * @version     $Revision$
 * @package     propel.engine.behavior
 */
class SortableBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'add_columns' => 'true',
		'rank_column' => 'rank',
		'add_index'   => 'false',
		'rank_index'  => 'rank_index',
	);

	/**
	 * Add the rank_column to the current table
	 */
	public function modifyTable()
	{
		if ($this->getParameter('add_columns') == 'true') {
			$this->getTable()->addColumn(array(
				'name' => $this->getParameter('rank_column'),
				'type' => 'INTEGER'
			));
		}
		if ($this->getParameter('add_index') == 'true') {
			$index = new Index($this->getColumnForParameter('rank_column'));
			$index->setName($this->getParameter('rank_index'));
			$index->addColumn($this->getTable()->getColumn($this->getParameter('rank_column')));
			$this->getTable()->addIndex($index);
		}
	}

	/**
	 * Get the getter of the column of the behavior
	 *
	 * @return string The related getter, e.g. 'getRank'
	 */
	protected function getColumnGetter()
	{
		return 'get' . $this->getColumnForParameter('rank_column')->getPhpName();
	}

	/**
	 * Get the setter of the column of the behavior
	 *
	 * @return string The related setter, e.g. 'setRank'
	 */
	protected function getColumnSetter()
	{
		return 'set' . $this->getColumnForParameter('rank_column')->getPhpName();
	}

	/**
	 * Add code in ObjectBuilder::preSave
	 *
	 * @return string The code to put at the hook
	 */
	public function preInsert($builder)
	{
		$this->builder = $builder;
		return <<<EOT
if (!\$this->isColumnModified({$this->getRankColumnConstant()})) {
	\$this->{$this->getColumnSetter()}({$this->getTable()->getPhpName()}Peer::getMaxPosition() + 1);
}
EOT;
	}
	

	/**
	 * Add code in ObjectBuilder::preDelete
	 *
	 * @return string The code to put at the hook
	 */
	public function preDelete()
	{
		return <<<EOT
\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
\$query = sprintf('UPDATE %s SET %s = %s - 1 WHERE %s > ?',
	'{$this->getTable()->getName()}',
	'{$this->getColumnForParameter('rank_column')->getName()}',
	'{$this->getColumnForParameter('rank_column')->getName()}',
	'{$this->getColumnForParameter('rank_column')->getName()}');
\$position = \$this->{$this->getColumnGetter()}();
\$stmt = \$con->prepare(\$query);
\$stmt->bindParam(1, \$position);
\$stmt->execute();
EOT;
	}
	
	protected function getRankColumnConstant()
	{
		return $this->builder->getColumnConstant($this->getColumnForParameter('rank_column'), $this->getTable()->getPhpName() . 'Peer');
	}
	/**
	 * Static methods
	 *
	 * @return string
	 */
	public function staticMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		$this->addGetMaxPosition($script);
		$this->addRetrieveByPosition($script);
		$this->addDoSort($script);
		$this->addDoSelectOrderByPosition($script);
		
		return $script;
	}
	
	protected function addGetMaxPosition(&$script)
	{
		$script .= "
/**
 * Get the highest position
 * @param	PropelPDO optional connection
 * @return integer	 highest position
 */
public static function getMaxPosition(PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
	}
	// shift the objects with a position lower than the one of object
	\$sql = sprintf('SELECT MAX(%s) FROM %s',
		'{$this->getColumnForParameter('rank_column')->getPhpName()}',
		'{$this->getTable()->getName()}');
	\$stmt = \$con->prepare(\$sql);
	\$stmt->execute();

	return \$stmt->fetchColumn();
}
";
	}
	
	protected function addRetrieveByPosition(&$script)
	{
		$script .= "
/**
 * Get an item from the list based on its position
 *
 * @param	integer	 \$position position
 * @param	PropelPDO \$con			optional connection
 * @return {$this->getTable()->getPhpName()}
 */
public static function retrieveByPosition(\$position, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
	}

	\$c = new Criteria;
	\$c->add({$this->getRankColumnConstant()}, \$position);

	return self::doSelectOne(\$c, \$con);
}
";
	}

	protected function addDoSort(&$script)
	{
		$script .= "
/**
 * Reorder a set of sortable objects based on a list of id/position
 * Beware that there is no check made on the positions passed
 * So incoherent positions will result in an incoherent list
 *
 * @param	array			\$order	id/position pairs
 * @param	PropelPDO	\$con		optional connection
 * @return boolean					true if the reordering took place, false if a database problem prevented it
 */
public static function doSort(array \$order, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
	}

	try {
		\$con->beginTransaction();

		foreach (\$order as \$id => \$rank) {
			\$c = new Criteria;
			\$c->add({$this->getRankColumnConstant()}, \$id);
			\${$this->getTable()->getPhpName()} = self::doSelectOne(\$c);

			if (\${$this->getTable()->getPhpName()} && \${$this->getTable()->getPhpName()}->getPosition() != \$rank) {
				\${$this->getTable()->getPhpName()}->setPosition(\$rank);
				\${$this->getTable()->getPhpName()}->save();
			}
		}

		\$con->commit();

		return true;
	} catch (Exception \$e) {
		\$con->rollback();

		return false;
	}
}
";
	}
	
		protected function addDoSelectOrderByPosition(&$script)
	{
		$script .= "
/**
 * Return an array of sortable objects ordered by position
 *
 * @param	string		\$order			sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
 * @param	Criteria	\$criteria	optional criteria object
 * @param	PropelPDO \$con				optional connection
 * @return array								list of sortable objects
 */
public static function doSelectOrderByPosition(\$order = Criteria::ASC, Criteria \$criteria = null, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
	}

	if (\$criteria === null) {
		\$criteria = new Criteria();
	} elseif (\$criteria instanceof Criteria) {
		\$criteria = clone \$criteria;
	}

	\$criteria->clearOrderByColumns();

	if (\$order == Criteria::ASC) {
		\$criteria->addAscendingOrderByColumn({$this->getRankColumnConstant()});
	} else {
		\$criteria->addDescendingOrderByColumn({$this->getRankColumnConstant()});
	}

	return self::doSelect(\$criteria, \$con);
}
";
	}
		
	/**
	 * Class methods
	 *
	 * @return string
	 */
	public function objectMethods()
	{
		$script = '';
		if ($this->getParameter('rank_column') != 'rank') {
			$this->addRankAccessors($script);
		}
		$this->addIsFirst($script);
		$this->addIsLast($script);
		$this->addGetNext($script);
		$this->addGetPrevious($script);
		$this->addInsertAtPosition($script);
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
 * @return  int
 */
public function getRank()
{
	return \$this->{$this->getColumnGetter()}();
}

/**
 * Wrap the setter for position value
 *
 * @param   int
 * @return  {$this->getTable()->getPhpName()}
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
 * @return boolean
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
 * @return boolean
 */
public function isLast()
{
	return \$this->{$this->getColumnGetter()}() == {$this->getTable()->getPhpName()}Peer::getMaxPosition();
}
";
	}

	protected function addGetNext(&$script)
	{
		$script .= "
/**
 * Get the next item in the list, i.e. the one for which position is immediately higher
 *
 * @return {$this->getTable()->getPhpName()}
 */
public function getNext()
{
	return {$this->getTable()->getPhpName()}Peer::retrieveByPosition(\$this->{$this->getColumnGetter()}() + 1);
}
";
	}

	protected function addGetPrevious(&$script)
	{
		$script .= "
/**
 * Get the previous item in the list, i.e. the one for which position is immediately lower
 *
 * @return {$this->getTable()->getPhpName()}
 */
public function getPrevious()
{
	return {$this->getTable()->getPhpName()}Peer::retrieveByPosition(\$this->{$this->getColumnGetter()}() - 1);
}
";
	}

	protected function addInsertAtPosition(&$script)
	{
		$script .= "
/**
 * insert at specified position
 *
 * @param	integer		\$position	position value
 * @param	PropelPDO	\$con				optional connection
 * @return integer							the new position
 * @throws PropelException
 */
public function insertAtPosition(\$position, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
	}
	\$con->beginTransaction();
	try {
		// shift the objects with a position higher than the given position
		\$query = sprintf('UPDATE %s SET %s = %s + 1 WHERE %s >= ?',
			'{$this->getTable()->getName()}',
			'{$this->getColumnForParameter('rank_column')->getName()}',
			'{$this->getColumnForParameter('rank_column')->getName()}',
			'{$this->getColumnForParameter('rank_column')->getName()}');
		\$stmt = \$con->prepare(\$query);
		\$stmt->bindParam(1, \$position);
		\$stmt->execute();

		// move the object in the list, at the given position
		\$this->{$this->getColumnSetter()}(\$position);
		\$this->save();

		\$con->commit();
		return \$position;
	} catch (Exception \$e) {
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
	\$this->insertAtPosition({$this->getTable()->getPhpName()}Peer::getMaxPosition(), \$con);
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
	\$this->insertAtPosition(1, \$con);
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
		\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
	}

	\$oldPosition = \$this->{$this->getColumnGetter()}();
	if (\$oldPosition == \$newPosition) {
		return \$oldPosition;
	}

	try {
		\$con->beginTransaction();

		// move the object away
		\$this->{$this->getColumnSetter()}({$this->getTable()->getPhpName()}Peer::getMaxPosition() + 1);
		\$this->save();

		// shift the objects between the old and the new position
		\$query = sprintf('UPDATE %s SET %s = %s %s 1 WHERE %s BETWEEN ? AND ?',
			'{$this->getTable()->getName()}',
			'{$this->getColumnForParameter('rank_column')->getName()}',
			'{$this->getColumnForParameter('rank_column')->getName()}',
			(\$oldPosition < \$newPosition) ? '-' : '+',
			'{$this->getColumnForParameter('rank_column')->getName()}'
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
 * @param	{$this->getTable()->getPhpName()} \${$this->getTable()->getPhpName()}
 * @param	PropelPDO \$con optional connection
 * @return array					the swapped ranks
 * @throws Exception if the database cannot execute the two updates
 */
public function swapWith({$this->getTable()->getPhpName()} \${$this->getTable()->getPhpName()}, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
	}

	try {
		\$con->beginTransaction();
		\$oldRank = \$this->{$this->getColumnGetter()}();
		\$newRank = \${$this->getTable()->getPhpName()}->{$this->getColumnGetter()}();
		\$this->{$this->getColumnSetter()}(\$newRank);
		\$this->save();
		\${$this->getTable()->getPhpName()}{$this->getColumnSetter()}($oldRank);
		\${$this->getTable()->getPhpName()}->save();
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
	return \$this->moveToPosition({$this->getTable()->getPhpName()}Peer::getMaxPosition());
}
";
	}
}
