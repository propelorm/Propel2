<?php

/*
 *  $Id: PropelCollection.php $
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
 * Class for iterating over a list of Propel objects stored as arrays
 *
 * @author     Francois Zaninotto
 * @package    propel.runtime.collection
 */
class PropelArrayCollection extends PropelCollection
{
	protected $workerObject;

	/**
	 * Save all the elements in the collection
	 */
	public function save($con = null)
	{
		if (null === $con) {
			$con = $this->getConnection(Propel::CONNECTION_WRITE);
		}
		$con->beginTransaction();
		try {
			$obj = $this->getWorkerObject();
			foreach ($this as $element) {
				$obj->clear();
				$obj->fromArray($element);
				$obj->setNew($obj->isPrimaryKeyNull());
				$obj->save($con);
			}
			$con->commit();
		} catch (PropelException $e) {
			$con->rollback();
		}
	}
	
	/**
	 * Delete all the elements in the collection
	 */
	public function delete($con = null)
	{
		if (null === $con) {
			$con = $this->getConnection(Propel::CONNECTION_WRITE);
		}
		$con->beginTransaction();
		try {
			foreach ($this as $element) {
				$obj = $this->getWorkerObject();
				$obj->setDeleted(false);
				$obj->fromArray($element);
				$obj->delete($con);
			}
			$con->commit();
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
	}

	/**
	 * Get an array of the primary keys of all the objects in the collection
	 *
	 * @return    array The list of the primary keys of the collection
	 */
	public function getPrimaryKeys($usePrefix = true)
	{
		$callable = array($this->getPeerClass(), 'getPrimaryKeyFromRow');
		$ret = array();
		foreach ($this as $key => $element) {
			$key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
			$ret[$key]= call_user_func($callable, array_values($element));
		}
		
		return $ret;
	}

	/**
	 * Populates the collection from an array
	 * Uses the object model to force the column types
	 * Does not empty the collection before adding the data from the array
	 *
	 * @param    array $arr
	 */
	public function fromArray($arr)
	{
		$obj  = $this->getWorkerObject();
		foreach ($arr as $element) {
			$obj->clear();
			$obj->fromArray($element);
			$this->append($obj->toArray());
		}
	}
	
	/**
	 * Get an array representation of the collection
	 * This is not an alias for getData(), since it returns a copy of the data
	 *
	 * @return    array
	 */
	public function toArray($usePrefix = true)
	{
		$ret = array();
		foreach ($this as $key => $element) {
			$key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
			$ret[$key] = $element;
		}

		return $ret;
	}

	protected function getWorkerObject()
	{
		if (null === $this->workerObject) {
			if ($this->model == '') {
				throw new PropelException('You must set the collection model before interacting with it');
			}
			$class = $this->getModel();
			$this->workerObject = new $class();
		}
		
		return $this->workerObject;
	}

}

?>