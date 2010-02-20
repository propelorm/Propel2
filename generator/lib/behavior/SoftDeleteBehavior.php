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
 * Gives a model class the ability to remain in database even when the user deletes object
 * Uses an additional column storing the deletion date
 * And an additional condition for every read query to only consider rows with no deletion date
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.behavior
 */
class SoftDeleteBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'deleted_column' => 'deleted_at',
	);
	
	/**
	 * Add the deleted_column to the current table
	 */
	public function modifyTable()
	{
		if(!$this->getTable()->containsColumn($this->getParameter('deleted_column'))) {
			$this->getTable()->addColumn(array(
				'name' => $this->getParameter('deleted_column'),
				'type' => 'TIMESTAMP'
			));
		}
	}
	
	protected function getColumnSetter()
	{
		return 'set' . $this->getColumnForParameter('deleted_column')->getPhpName();
	}

	public function objectMethods($builder)
	{
		$script = '';
		$this->addObjectForceDelete($script);
		$this->addObjectUndelete($script);
		return $script;
	}
	
	public function addObjectForceDelete(&$script)
	{
		$script .= "
/**
 * Bypass the soft_delete behavior and force a hard delete of the current object
 */
public function forceDelete(PropelPDO \$con = null)
{
	{$this->getTable()->getPhpName()}Peer::disableSoftDelete();
	\$this->delete(\$con);
}
";
	}
	
	public function addObjectUndelete(&$script)
	{
		$script .= "
/**
 * Undelete a row that was soft_deleted
 *
 * @return		 int The number of rows affected by this update and any referring fk objects' save() operations.
 */
public function unDelete(PropelPDO \$con = null)
{
	\$this->{$this->getColumnSetter()}(null);
	return \$this->save(\$con);
}
";
	}

	public function preDelete($builder)
	{
		return <<<EOT
if (!empty(\$ret) && {$builder->getStubQueryBuilder()->getClassname()}::isSoftDeleteEnabled()) {
	\$this->{$this->getColumnSetter()}(time());
	\$this->save();
	\$con->commit();
	return;
}
EOT;
	}

	public function queryAttributes()
	{
		return "protected static \$softDelete = true;
protected \$localSoftDelete = true;
";
	}

	public function queryMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		$this->addQueryIncludeDeleted($script);
		$this->addQuerySoftDelete($script);
		$this->addQueryForceDelete($script);
		$this->addQueryForceDeleteAll($script);
		$this->addQueryUnDelete($script);
		$this->addQueryEnableSoftDelete($script);
		$this->addQueryDisableSoftDelete($script);
		$this->addQueryIsSoftDeleteEnabled($script);
		
		return $script;
	}
	
	public function addQueryIncludeDeleted(&$script)
	{
		$script .= "
/**
 * Temporarily disable the filter on deleted rows
 * Valid only for the current query
 * 
 * @see {$this->builder->getStubQueryBuilder()->getClassname()}::disableSoftDelete() to disable the filter for more than one query
 *
 * @return {$this->builder->getStubQueryBuilder()->getClassname()} The current query, for fuid interface
 */
public function includeDeleted()
{
	\$this->localSoftDelete = false;
	return \$this;
}
";
	}
	
	public function addQuerySoftDelete(&$script)
	{
		$script .= "
/**
 * Soft delete the selected rows
 *
 * @param			PropelPDO \$con an optional connection object
 *
 * @return		int Number of updated rows
 */
public function softDelete(PropelPDO \$con = null)
{
	return \$this->update(array('{$this->getColumnForParameter('deleted_column')->getPhpName()}' => time()), \$con);
}
";
	}
	
	public function addQueryForceDelete(&$script)
	{
		$script .= "
/**
 * Bypass the soft_delete behavior and force a hard delete of the selected rows
 *
 * @param			PropelPDO \$con an optional connection object
 *
 * @return		int Number of deleted rows
 */
public function forceDelete(PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->builder->getPeerClassname()}::DATABASE_NAME, Propel::CONNECTION_WRITE);
	}
	return \$this->doDelete(\$con);
}
";
	}

	public function addQueryForceDeleteAll(&$script)
	{
		$script .= "
/**
 * Bypass the soft_delete behavior and force a hard delete of all the rows
 *
 * @param			PropelPDO \$con an optional connection object
 *
 * @return		int Number of deleted rows
 */
public function forceDeleteAll(PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->builder->getPeerClassname()}::DATABASE_NAME, Propel::CONNECTION_WRITE);
	}
	return \$this->doDeleteAll(\$con);
}
";
	}

	public function addQueryUnDelete(&$script)
	{
		$script .= "
/**
 * Undelete selected rows
 *
 * @param			PropelPDO \$con an optional connection object
 *
 * @return		int The number of rows affected by this update and any referring fk objects' save() operations.
 */
public function unDelete(PropelPDO \$con = null)
{
	return \$this->update(array('{$this->getColumnForParameter('deleted_column')->getPhpName()}' => null), \$con);
}
";
	}

	public function addQueryEnableSoftDelete(&$script)
	{
		$script .= "	
/**
 * Enable the soft_delete behavior for this model
 */
public static function enableSoftDelete()
{
	self::\$softDelete = true;
}
";
	}

	public function addQueryDisableSoftDelete(&$script)
	{
		$script .= "
/**
 * Disable the soft_delete behavior for this model
 */
public static function disableSoftDelete()
{
	self::\$softDelete = false;
}
";
	}

	public function addQueryIsSoftDeleteEnabled(&$script)
	{
		$script .= "
/**
 * Check the soft_delete behavior for this model
 *
 * @return boolean true if the soft_delete behavior is enabled
 */
public static function isSoftDeleteEnabled()
{
	return self::\$softDelete;
}
";
	}

	public function preSelectQuery($builder)
	{
		return <<<EOT
if ({$builder->getStubQueryBuilder()->getClassname()}::isSoftDeleteEnabled() && \$this->localSoftDelete) {
	\$this->addUsingAlias({$this->getColumnForParameter('deleted_column')->getConstantName()}, null, Criteria::ISNULL);
} else {
	{$this->getTable()->getPhpName()}Peer::enableSoftDelete();
}
EOT;
	}

	public function preDeleteQuery($builder)
	{
		return <<<EOT
if ({$builder->getStubQueryBuilder()->getClassname()}::isSoftDeleteEnabled() && \$this->localSoftDelete) {
	return \$this->softDelete(\$con);
} else {
	return \$this->hasWhereClause() ? \$this->forceDelete(\$con) : \$this->forceDeleteAll(\$con);
}
EOT;
	}

	public function staticMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		$this->addPeerEnableSoftDelete($script);
		$this->addPeerDisableSoftDelete($script);
		$this->addPeerIsSoftDeleteEnabled($script);
		$this->addPeerDoSoftDelete($script);
		$this->addPeerDoDelete2($script);
		$this->addPeerDoSoftDeleteAll($script);
		$this->addPeerDoDeleteAll2($script);
		
		return $script;
	}

	public function addPeerEnableSoftDelete(&$script)
	{
		$script .= "
/**
 * Enable the soft_delete behavior for this model
 */
public static function enableSoftDelete()
{
	{$this->builder->getStubQueryBuilder()->getClassname()}::enableSoftDelete();
}
";
	}

	public function addPeerDisableSoftDelete(&$script)
	{
		$script .= "
/**
 * Disable the soft_delete behavior for this model
 */
public static function disableSoftDelete()
{
	{$this->builder->getStubQueryBuilder()->getClassname()}::disableSoftDelete();
}
";
	}
	
	public function addPeerIsSoftDeleteEnabled(&$script)
	{
		$script .= "
/**
 * Check the soft_delete behavior for this model
 * @return boolean true if the soft_delete behavior is enabled
 */
public static function isSoftDeleteEnabled()
{
	return {$this->builder->getStubQueryBuilder()->getClassname()}::isSoftDeleteEnabled();
}
";
	}

	public function addPeerDoSoftDelete(&$script)
	{
		$script .= "
/**
 * Soft delete records, given a {$this->getTable()->getPhpName()} or Criteria object OR a primary key value.
 *
 * @param			 mixed \$values Criteria or {$this->getTable()->getPhpName()} object or primary key or array of primary keys
 *							which is used to create the DELETE statement
 * @param			 PropelPDO \$con the connection to use
 * @return		 int	The number of affected rows (if supported by underlying database driver).
 * @throws		 PropelException Any exceptions caught during processing will be
 *							rethrown wrapped into a PropelException.
 */
public static function doSoftDelete(\$values, PropelPDO \$con = null)
{
	if (\$values instanceof Criteria) {
		// rename for clarity
		\$criteria = clone \$values;
	} elseif (\$values instanceof {$this->getTable()->getPhpName()}) {
		// create criteria based on pk values
		\$criteria = \$values->buildPkeyCriteria();
	} else {
		// it must be the primary key
		\$criteria = new Criteria(self::DATABASE_NAME);
		\$criteria->add({$this->getTable()->getPhpName()}Peer::ID, (array) \$values, Criteria::IN);
	}
	\$criteria->add({$this->getColumnForParameter('deleted_column')->getConstantName()}, time());
	return {$this->getTable()->getPhpName()}Peer::doUpdate(\$criteria, \$con);
}
";
	}

	public function addPeerDoDelete2(&$script)
	{
		$script .= "
/**
 * Delete or soft delete records, depending on {$this->getTable()->getPhpName()}Peer::\$softDelete
 *
 * @param			 mixed \$values Criteria or {$this->getTable()->getPhpName()} object or primary key or array of primary keys
 *							which is used to create the DELETE statement
 * @param			 PropelPDO \$con the connection to use
 * @return		 int	The number of affected rows (if supported by underlying database driver).
 * @throws		 PropelException Any exceptions caught during processing will be
 *							rethrown wrapped into a PropelException.
 */
public static function doDelete2(\$values, PropelPDO \$con = null)
{
	if ({$this->getTable()->getPhpName()}Peer::isSoftDeleteEnabled()) {
		return {$this->getTable()->getPhpName()}Peer::doSoftDelete(\$values, \$con);
	} else {
		return {$this->getTable()->getPhpName()}Peer::doForceDelete(\$values, \$con);
	} 
}";
	}

	public function addPeerDoSoftDeleteAll(&$script)
	{
		$script .= "
/**
 * Method to soft delete all rows from the {$this->getTable()->getName()} table.
 *
 * @param			 PropelPDO \$con the connection to use
 * @return		 int The number of affected rows (if supported by underlying database driver).
 * @throws		 PropelException Any exceptions caught during processing will be
 *							rethrown wrapped into a PropelException.
 */
public static function doSoftDeleteAll(PropelPDO \$con = null)
{
	if (\$con === null) {
		\$con = Propel::getConnection({$this->getTable()->getPhpName()}Peer::DATABASE_NAME, Propel::CONNECTION_WRITE);
	}
	\$selectCriteria = new Criteria();
	\$selectCriteria->add({$this->getColumnForParameter('deleted_column')->getConstantName()}, null, Criteria::ISNULL);
	\$selectCriteria->setDbName({$this->getTable()->getPhpName()}Peer::DATABASE_NAME);
	\$modifyCriteria = new Criteria();
	\$modifyCriteria->add({$this->getColumnForParameter('deleted_column')->getConstantName()}, time());
	return BasePeer::doUpdate(\$selectCriteria, \$modifyCriteria, \$con);
}
";
	}
	
	public function addPeerDoDeleteAll2(&$script)
	{
		$script .= "
/**
 * Delete or soft delete all records, depending on {$this->getTable()->getPhpName()}Peer::\$softDelete
 *
 * @param			 PropelPDO \$con the connection to use
 * @return		 int	The number of affected rows (if supported by underlying database driver).
 * @throws		 PropelException Any exceptions caught during processing will be
 *							rethrown wrapped into a PropelException.
 */
public static function doDeleteAll2(PropelPDO \$con = null)
{
	if ({$this->getTable()->getPhpName()}Peer::isSoftDeleteEnabled()) {
		return {$this->getTable()->getPhpName()}Peer::doSoftDeleteAll(\$con);
	} else {
		return {$this->getTable()->getPhpName()}Peer::doForceDeleteAll(\$con);
	} 
}
";
	}

	public function preSelect($builder)
	{
		return <<<EOT
if ({$builder->getStubQueryBuilder()->getClassname()}::isSoftDeleteEnabled()) {
	\$criteria->add({$this->getColumnForParameter('deleted_column')->getConstantName()}, null, Criteria::ISNULL);
} else {
	{$this->getTable()->getPhpName()}Peer::enableSoftDelete();
}
EOT;
	}
		
	public function peerFilter(&$script)
	{
		$script = str_replace(array(
			'public static function doDelete(', 
			'public static function doDelete2(',
			'public static function doDeleteAll(', 
			'public static function doDeleteAll2('
		), array(
			'public static function doForceDelete(',
			'public static function doDelete(',
			'public static function doForceDeleteAll(',
			'public static function doDeleteAll('
		), $script);
	}
}