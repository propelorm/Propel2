<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Boosts basic CRUD operations at runtime.
 *
 * @author     François Zaninotto
 * @package    propel.generator.behavior
 */
class TurboBehavior extends Behavior
{
  
  protected $builder;
  
	public function queryMethods($builder)
	{
		$script = '';
		$script .= $this->addFindPkSimple($builder);
		$script .= $this->addFindPkTurbo($builder);

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
		foreach ($table->getPrimaryKey() as $column) {
			$conditions []= sprintf('%s = ?', $this->getColumnIdentifier($column, $platform));
		}
		$query = sprintf(
			'SELECT %s FROM %s WHERE %s',
			implode(', ', $selectColumns),
			$platform->quoteIdentifier($table->getName()),
			implode(' AND ', $conditions)
		);
		$binding = $table->hasCompositePrimaryKey() ? '$key' : 'array($key)';
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
		return "
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
	\$stmt = \$con->prepare('$query');
	\$stmt->execute($binding);
	if (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
		\$obj = new $ARClassname();
		\$obj->hydrate(\$row);
		BookPeer::addInstanceToPool(\$obj, $pkHashFromRow);
	}
	\$stmt->closeCursor();
	
	return \$obj;
}
";
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
		$script = str_replace('public function findPk(', 'public function findPkComplex(', $script);
		$script = str_replace('public function findPkTurbo(', 'public function findPk(', $script);
	}

}