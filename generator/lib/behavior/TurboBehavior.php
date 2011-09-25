<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../model/PropelTypes.php';

/**
 * Boosts some basic CRUD operations at runtime by pregenerating the query 
 * and hydration code.
 * Warning: 
 *  - The doInsert acceleration is not compatible with models using
 *    a preSelect() hook (or a behavior using it, like soft_delete).
 *  - The findPk acceleration is not compatible with models using BLOBs
 *    on the MSSQL platform (because of cleanupSQL magic).
 *
 * @author     François Zaninotto
 * @package    propel.generator.behavior
 */
class TurboBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'accelerate_doInsert' => 'true',
		'accelerate_findPk'   => 'true'
	);

	public function objectMethods($builder)
	{
		$script = '';
		if ($this->getParameter('accelerate_doInsert') == 'true') {
			$script .= $this->addDoInsertTurbo($builder);
		}

		return $script;
	}
	
	/**
	 * Boosts ActiveRecord::doInsert() by doing more calculations at buildtime.
	 */
	protected function addDoInsertTurbo($builder)
	{
		$table = $this->getTable();
		$peerClassname = $builder->getPeerClassname();
		$platform = $builder->getPlatform();
		$primaryKeyMethodInfo = '';
		if ($table->getIdMethodParameters()) {
			$params = $table->getIdMethodParameters();
			$imp = $params[0];
			$primaryKeyMethodInfo = ", '" . $imp->getValue() . "'";
		} elseif ($table->getIdMethod() == IDMethod::NATIVE && ($platform->getNativeIdMethod() == PropelPlatformInterface::SEQUENCE || $platform->getNativeIdMethod() == PropelPlatformInterface::SERIAL)) {
			$primaryKeyMethodInfo = ", '" . $platform->getSequenceName($table) . "'";
		}
		$query = 'INSERT INTO ' . $platform->quoteIdentifier($table->getName()) . ' (%s) VALUES (%s)';
		$script = "
/**
 * Insert the row in the database.
 *
 * @param      PropelPDO \$con
 *
 * @throws     PropelException
 * @see        doSave()
 */
protected function doInsertTurbo(PropelPDO \$con)
{
	\$adapter = Propel::getDB({$peerClassname}::DATABASE_NAME);
	\$modifiedColumnNames = \$modifiedColumnValues = \$modifiedColumnTypes = \$parameters = array();
	\$index = 0;";
	
		// if non auto-increment but using sequence, get the id first
		if (!$platform->isNativeIdMethodAutoIncrement() && $table->getIdMethod() == "native") {
			$column = $table->getFirstPrimaryKeyColumn();
			$columnProperty = strtolower($column->getName());
			$identifier = $this->getColumnIdentifier($column, $platform);
			$script .= "
	if (null === \$this->{$columnProperty}) {
		try {
			\$pk = \$adapter->getId(\$con{$primaryKeyMethodInfo});
		} catch (Exception \$e) {
			throw new PropelException('Unable to get sequence id.', \$e);
		}
		\$modifiedColumnNames[]  = '$identifier';
		\$modifiedColumnValues[] = \$pk;
		\$modifiedColumnTypes[]  = " . PropelTypes::getPdoTypeString($column->getType()) . ";
		\$parameters[] = ':p' . \$index++;
	}";
		}
		
		foreach ($table->getColumns() as $column) {
			$columnProperty = strtolower($column->getName());
			$constantName = $builder->getPeerBuilder()->getColumnConstant($column);
			$identifier = $this->getColumnIdentifier($column, $platform);
			$script .= "
	if (\$this->isColumnModified($constantName) && null !== \$this->{$columnProperty}) {";
			if ($column->isPrimaryKey() && $column->isAutoIncrement()) {
				if (!$table->isAllowPkInsert()) {
					$script .= "
		throw new PropelException('Cannot insert a value for auto-increment primary key ($columnProperty)');
	}";
					continue;
				}
			} elseif (!$platform->supportsInsertNullPk()) {
				$script .= "
		if (null === \$this->$columnProperty) {
			continue;
		}";
			}
			$script .= "
		\$modifiedColumnNames[]  = '$identifier';
		\$modifiedColumnValues[] = \$this->$columnProperty;
		\$modifiedColumnTypes[]  = " . PropelTypes::getPdoTypeString($column->getType()) . ";
		\$parameters[] = ':p' . \$index++;
	}";
		}

		$script .= "
	\$query = sprintf(
		'$query',
		implode(', ', \$modifiedColumnNames),
		implode(', ', \$parameters)
	);
	
	try {
		\$stmt = \$con->prepare(\$query);
		foreach (\$parameters as \$index => \$name) {
			\$stmt->bindValue(\$name, \$modifiedColumnValues[\$index], \$modifiedColumnTypes[\$index]);
		}
		\$stmt->execute();
	} catch (Exception \$e) {
		Propel::log(\$e->getMessage(), Propel::LOG_ERR);
		throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', \$query), \$e);
	}
";

		// if auto-increment, get the id after
		if ($platform->isNativeIdMethodAutoIncrement() && $table->getIdMethod() == "native") {
			$column = $table->getFirstPrimaryKeyColumn();
			$columnProperty = strtolower($column->getName());
			$script .= "
	try {
		\$pk = \$adapter->getId(\$con{$primaryKeyMethodInfo});
	} catch (Exception \$e) {
		throw new PropelException('Unable to get autoincrement id.', \$e);
	}";
			if ($table->isAllowPkInsert()) {
				$script .= "
	if (\$pk !== null) {
		\$this->set".$column->getPhpName()."(\$pk);
	}";
			} else {
				$script .= "
	\$this->set".$column->getPhpName()."(\$pk);";
			}
			$script .= "
";
		}

		$script .= "
	\$this->setNew(false);
}
";
		return $script;
	}
	
	public function objectFilter(&$script)
	{
		if ($this->getParameter('accelerate_doInsert') == 'true') {
			$script = str_replace('protected function doInsert(', 'protected function doInsertUsingBasePeer(', $script);
			$script = str_replace('protected function doInsertTurbo(', 'protected function doInsert(', $script);
		}
	}


	/**
	 * Replace the generated findPk() method by one that takes a shortcut if the query is untouched.
	 */
	public function queryMethods($builder)
	{
		if ($this->getTable()->hasBehavior('soft_delete')) {
			// soft_delete uses a preSelect hook, and the findPkTurbo method cannot work with that
			return;
		}
		$script = '';
		if ($this->getParameter('accelerate_findPk') == 'true') {
			$script .= $this->addFindPkSimple($builder);
			$script .= $this->addFindPkTurbo($builder);
		}

		return $script;
	}
	
	protected static function getColumnIdentifier($column, $platform)
	{
		return $platform->quoteIdentifier(strtoupper($column->getName()));
	}

	protected function addFindPkSimple($builder)
	{
		$table = $this->getTable();
		$platform = $builder->getPlatform();
		$peerClassname = $builder->getPeerClassname();
		$ARClassname = $builder->getObjectClassname();
		$selectColumns = array();
		foreach ($table->getColumns() as $column) {
			if (!$column->isLazyLoad()) {
				$selectColumns []= $this->getColumnIdentifier($column, $platform);
			}
		}
		$conditions = array();
		foreach ($table->getPrimaryKey() as $index => $column) {
			$conditions []= sprintf('%s = :p%d', $this->getColumnIdentifier($column, $platform), $index);
		}
		$query = sprintf(
			'SELECT %s FROM %s WHERE %s',
			implode(', ', $selectColumns),
			$platform->quoteIdentifier($table->getName()),
			implode(' AND ', $conditions)
		);
		if ($table->hasCompositePrimaryKey()) {
			$pks = array();
			foreach ($table->getPrimaryKey() as $index => $column) {
				$pks []= "\$key[$index]";
			}
		} else {
			$pks = '$key';
		}
		$pkHash = $builder->getPeerBuilder()->getInstancePoolKeySnippet($pks);
		$pks = array();
		foreach ($table->getPrimaryKey() as $index => $column) {
			$pks []= '(' . $column->getPhpType() . ") \$row[$index]";
		}
		$pkHashFromRow = $builder->getPeerBuilder()->getInstancePoolKeySnippet($pks);
		$docBlock = $this->getFindPkDocBlock('findPkSimple');
		$script = "
/**
 * Find object by primary key using raw SQL to go fast.
$docBlock
 *
 * @return    $ARClassname A model object, or null if the key is not found
 */
public function findPkSimple(\$key, \$con = null)
{
	if ((null !== (\$obj = {$peerClassname}::getInstanceFromPool($pkHash)))) {
		// the object is already in the instance pool
		return \$obj;
	}
	if (\$con === null) {
		\$con = Propel::getConnection({$peerClassname}::DATABASE_NAME, Propel::CONNECTION_READ);
	}
	\$stmt = \$con->prepare('$query');";
		if ($table->hasCompositePrimaryKey()) {
			foreach ($table->getPrimaryKey() as $index => $column) {
				$type = PropelTypes::getPdoTypeString($column->getType());
				$script .= "
	\$stmt->bindValue(':p$index', \$key[$index], $type);";
			}
		} else {
				$pk = $table->getPrimaryKey();
				$column = $pk[0];
				$type = PropelTypes::getPdoTypeString($column->getType());
				$script .= "
	\$stmt->bindValue(':p0', \$key, $type);";
		}
		$script .= "
	\$stmt->execute();
	if (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
		\$obj = new $ARClassname();
		\$obj->hydrate(\$row);
		{$peerClassname}::addInstanceToPool(\$obj, $pkHashFromRow);
	}
	\$stmt->closeCursor();
	
	return \$obj;
}
";
		return $script;
	}
	
	protected function addFindPkTurbo($builder)
	{
		$class = $builder->getObjectClassname();
		$docBlock = $this->getFindPkDocBlock('findPk');
		
		return "
/**
 * Find object by primary key.
 * Go fast if the query is untouched.
$docBlock
 *
 * @return    $class|array|mixed the result, formatted by the current formatter
 */
public function findPkTurbo(\$key, \$con = null)
{
	if (\$key === null) {
		return null;
	}
	
	if (\$this->formatter || \$this->modelAlias || \$this->with || \$this->select
	 || \$this->selectColumns || \$this->asColumns || \$this->selectModifiers 
	 || \$this->map || \$this->having || \$this->joins) {
		return \$this->findPkComplex(\$key, \$con);
	} else {
		return \$this->findPkSimple(\$key, \$con);
	}
}
";
	}

	protected function getFindPkDocBlock($methodName)
	{
		$pks = $this->getTable()->getPrimaryKey();
		$script = ' * Propel uses the instance pool to skip the database if the object exists.';
		if (count($pks) === 1) {
			$pkType = 'mixed';
			$script .= "
 * <code>
 * \$obj  = \$c->$methodName(12, \$con);";
		} else {
			$examplePk = array_slice(array(12, 34, 56, 78, 91), 0, count($pks));
			$colNames = array();
			foreach ($pks as $col) {
				$colNames[]= '$' . $col->getName();
			}
			$pkType = 'array['. join($colNames, ', ') . ']';
			$script .= "
 * <code>
 * \$obj = \$c->$methodName(array(" . join($examplePk, ', ') . "), \$con);";
		}
		$script .= "
 * </code>
 * @param     " . $pkType . " \$key Primary key to use for the query
 * @param     PropelPDO \$con an optional connection object";
		
		return $script;
	}

	public function queryFilter(&$script)
	{
		if ($this->getParameter('accelerate_findPk') == 'true') {
			$script = str_replace('public function findPk(', 'public function findPkComplex(', $script);
			$script = str_replace('public function findPkTurbo(', 'public function findPk(', $script);
		}
	}

}