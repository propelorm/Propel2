<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Class for iterating over a statement and returning one Propel object at a time
 *
 * @author     Francois Zaninotto
 * @package    propel.runtime.collection
 */
class PropelOnDemandCollection extends PropelCollection implements Iterator
{
	protected 
		$stmt, 
		$currentRow, 
		$currentKey = -1,
		$isValid = null;
	
	public function setStatement(PDOStatement $stmt)
	{
		$this->stmt = $stmt;
	}
	
	public function closeCursor()
	{
		$this->stmt->closeCursor();
	}
	
	// IteratorAggregate Interface
	
	public function getIterator()
	{
		return $this;
	}

	// Iterator Interface
	
	/**
	 * Gets the current Model object in the collection
	 * This is where the hydration takes place.
	 *
	 * @see PropelObjectFormatter::getAllObjectsFromRow()
	 *
	 * @return    BaseObject
	 */
	public function current()
	{
		return $this->formatter->getAllObjectsFromRow($this->currentRow);
	}
	
	/**
	 * Gets the current key in the iterator
	 *
	 * @return    string
	 */
	public function key()
	{
		return $this->currentKey;
	}
	
	/**
	 * Advances the curesor in the statement
	 * Closes the cursor if the end of the statement is reached
	 */
	public function next()
	{
		$this->currentRow = $this->stmt->fetch(PDO::FETCH_NUM);
		$this->currentKey++;
		$this->isValid = (boolean) $this->currentRow;
		if (!$this->isValid) {
			$this->closeCursor();
		}
	}
	
	/**
	 * Initializes the iterator by advancing to the first position
	 * This method can only be called once (this is a NoRewindIterator)
	 */
	public function rewind()
	{
		// check that the hydration can begin
		if (null === $this->formatter) {
			throw new PropelException('The On Demand collection requires a formatter. Add it by calling setFormatter()');
		}
		if (null === $this->stmt) {
			throw new PropelException('The On Demand collection requires a statement. Add it by calling setStatement()');
		}
		if (null !== $this->isValid) {
			throw new PropelException('The On Demand collection can only be iterated once');
		}
		
		// initialize the current row and key
		$this->next();
	}
	
	public function valid()
	{
		return $this->isValid;
	}

	// ArrayAccess Interface
	
	public function offsetExists($offset)
	{
		if ($offset == $this->currentKey) {
			return true;
		}
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}

	public function offsetGet($offset)
	{
		if ($offset == $this->currentKey) {
			return $this->currentRow;
		}
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}
	
	public function offsetSet($offset, $value)
	{
		throw new PropelException('The On Demand Collection is read only');
	}

	public function offsetUnset($offset)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	// Serializable Interface
	
	public function serialize()
	{
		throw new PropelException('The On Demand Collection cannot be serialized');
	}

	public function unserialize($data)
	{
		throw new PropelException('The On Demand Collection cannot be serialized');
	}
	
	// Countable Interface
	
	/**
	 * Returns the number of rows in the resultset
	 * Warning: this number is inaccurate for most databases. Do not rely on it for a portable application.
	 * 
	 * @return    int number of results
	 */
	public function count()
	{
		return $this->stmt->rowCount();
	}
	
	// ArrayObject methods
	
	public function append($value)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function prepend($value)
	{
		throw new PropelException('The On Demand Collection is read only');
	}

	public function asort()
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function exchangeArray($input)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function getArrayCopy()
	{
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}
	
	public function getFlags()
	{
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}
	
	public function ksort()
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function natcasesort()
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function natsort()
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function setFlags($flags)
	{
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}
	
	public function uasort($cmp_function)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function uksort($cmp_function)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
}