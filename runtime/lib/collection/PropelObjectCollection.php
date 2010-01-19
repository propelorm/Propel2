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
 * Class for iterating over a list of Propel objects
 *
 * @author     Francois Zaninotto
 * @package    propel.runtime.collection
 */
class PropelObjectCollection extends PropelCollection
{	
	
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
			foreach ($this as $element) {
				$element->save($con);
			}
			$con->commit();
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
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
				$element->delete($con);
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
		$ret = array();
		foreach ($this as $key => $obj) {
			$key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
			$ret[$key]= $obj->getPrimaryKey();
		}
		
		return $ret;
	}

	/**
	 * Populates the collection from an array
	 * Each object is populated from an array and the result is stored
	 * Does not empty the collection before adding the data from the array
	 *
	 * @param    array $arr
	 */
	public function fromArray($arr)
	{
		$class = $this->getModel();
		foreach ($arr as $element) {
			$obj = new $class();
			$obj->fromArray($element);
			$this->append($obj);
		}
	}
	
	/**
	 * Get an array representation of the collection
	 * Each object is turned into an array and the result is returned
	 *
	 * @return    array
	 */
	public function toArray($usePrefix = true)
	{
		$ret = array();
		foreach ($this as $key => $obj) {
			$key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
			$ret[$key] = $obj->toArray();
		}
		
		return $ret;
	}

}

?>