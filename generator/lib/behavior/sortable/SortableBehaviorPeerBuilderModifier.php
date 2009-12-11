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

	protected function getColumnPhpName($name)
	{
		return $this->behavior->getColumnForParameter($name)->getPhpName();
	}
	
	protected function setBuilder($builder)
	{
		$this->builder = $builder;
		$this->objectClassname = $builder->getStubObjectBuilder()->getClassname();
		$this->peerClassname = $builder->getStubPeerBuilder()->getClassname();
		$this->rankColumn = $builder->getColumnConstant($this->behavior->getColumnForParameter('rank_column'), $this->table->getPhpName() . 'Peer');

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
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}
	// shift the objects with a position lower than the one of object
	\$sql = sprintf('SELECT MAX(%s) FROM %s',
		'{$this->behavior->getColumnForParameter('rank_column')->getPhpName()}',
		'{$this->table->getName()}');
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
 * @return {$this->objectClassname}
 */
public static function retrieveByPosition(\$position, PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}

	\$c = new Criteria;
	\$c->add({$this->rankColumn}, \$position);

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
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}

	try {
		\$con->beginTransaction();

		foreach (\$order as \$id => \$rank) {
			\$c = new Criteria;
			\$c->add({$this->rankColumn}, \$id);
			\$object = self::doSelectOne(\$c);

			if (\$object && \$object->getPosition() != \$rank) {
				\$object->setPosition(\$rank);
				\$object->save();
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
		\$con = Propel::getConnection({$this->peerClassname}::DATABASE_NAME);
	}

	if (\$criteria === null) {
		\$criteria = new Criteria();
	} elseif (\$criteria instanceof Criteria) {
		\$criteria = clone \$criteria;
	}

	\$criteria->clearOrderByColumns();

	if (\$order == Criteria::ASC) {
		\$criteria->addAscendingOrderByColumn({$this->rankColumn});
	} else {
		\$criteria->addDescendingOrderByColumn({$this->rankColumn});
	}

	return self::doSelect(\$criteria, \$con);
}
";
	}
	
}