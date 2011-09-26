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
 * Warning: The doInsert acceleration is not compatible with models using BLOBs
 * on the MSSQL platform (because of cleanupSQL magic).
 *
 * @author     François Zaninotto
 * @package    propel.generator.behavior
 */
class TurboBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'accelerate_doInsert' => 'true'
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
	\$modifiedColumns = array();
	\$index = 0;
	";
	
		// if non auto-increment but using sequence, get the id first
		if (!$platform->isNativeIdMethodAutoIncrement() && $table->getIdMethod() == "native") {
			$column = $table->getFirstPrimaryKeyColumn();
			$columnProperty = strtolower($column->getName());
			$identifier = $this->getColumnIdentifier($column, $platform);
			$script .= "
	if (null === \$this->{$columnProperty}) {
		try {
			\$this->{$columnProperty} = \$adapter->getId(\$con{$primaryKeyMethodInfo});
		} catch (Exception \$e) {
			throw new PropelException('Unable to get sequence id.', \$e);
		}
		\$modifiedColumns[':p' . \$index++]  = '$identifier';
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
		if (null === \$this->{$columnProperty}) {
			continue;
		}";
			}
			$script .= "
		\$modifiedColumns[':p' . \$index++]  = '$identifier';
	}";
		}

		$script .= "
	
	\$query = sprintf(
		'$query',
		implode(', ', \$modifiedColumns),
		implode(', ', array_keys(\$modifiedColumns))
	);
	
	try {
		\$stmt = \$con->prepare(\$query);
		foreach (\$modifiedColumns as \$identifier => \$columnName) {
			switch (\$columnName) {";
		foreach ($table->getColumns() as $column) {
			$columnNameCase = $this->getColumnIdentifier($column, $platform);
			$script .= "
				case '$columnNameCase':";
			$script .= $platform->getColumnBindingPHP($column, "\$identifier", '$this->' . strtolower($column->getName()), '					');
			$script .= "
					break;";
		}
		$script .= "
			}
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

	protected static function getColumnIdentifier($column, $platform)
	{
		return $platform->quoteIdentifier(strtoupper($column->getName()));
	}


	



}