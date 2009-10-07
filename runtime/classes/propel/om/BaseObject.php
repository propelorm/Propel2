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
 * This class contains attributes and methods that are used by all
 * business objects within the system.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @version    $Revision$
 * @package    propel.om
 */
abstract class BaseObject {

	/**
	 * attribute to determine if this object has previously been saved.
	 * @var        boolean
	 */
	private $_new = true;

	/**
	 * attribute to determine whether this object has been deleted.
	 * @var        boolean
	 */
	private $_deleted = false;

	/**
	 * The columns that have been modified in current object.
	 * Tracking modified columns allows us to only update modified columns.
	 * @var        array
	 */
	protected $modifiedColumns = array();

	/**
	 * Empty constructor (this allows people with their own BaseObject implementation to use its constructor)
	 */
	public function __construct() {

	}

	/**
	 * Returns whether the object has been modified.
	 *
	 * @return     boolean True if the object has been modified.
	 */
	public function isModified()
	{
		return !empty($this->modifiedColumns);
	}

	/**
	 * Has specified column been modified?
	 *
	 * @param      string $col
	 * @return     boolean True if $col has been modified.
	 */
	public function isColumnModified($col)
	{
		return in_array($col, $this->modifiedColumns);
	}

	/**
	 * Get the columns that have been modified in this object.
	 * @return     array A unique list of the modified column names for this object.
	 */
	public function getModifiedColumns()
	{
		return array_unique($this->modifiedColumns);
	}

	/**
	 * Returns whether the object has ever been saved.  This will
	 * be false, if the object was retrieved from storage or was created
	 * and then saved.
	 *
	 * @return     true, if the object has never been persisted.
	 */
	public function isNew()
	{
		return $this->_new;
	}

	/**
	 * Setter for the isNew attribute.  This method will be called
	 * by Propel-generated children and Peers.
	 *
	 * @param      boolean $b the state of the object.
	 */
	public function setNew($b)
	{
		$this->_new = (boolean) $b;
	}

	/**
	 * Whether this object has been deleted.
	 * @return     boolean The deleted state of this object.
	 */
	public function isDeleted()
	{
		return $this->_deleted;
	}

	/**
	 * Specify whether this object has been deleted.
	 * @param      boolean $b The deleted state of this object.
	 * @return     void
	 */
	public function setDeleted($b)
	{
		$this->_deleted = (boolean) $b;
	}

	/**
	 * Code to be run before persisting the object
	 * @param PropelPDO $con
	 * @return bloolean
	 */
	public function preSave(PropelPDO $con = null)
	{
		return true;
	}

	/**
	 * Code to be run after persisting the object
	 * @param PropelPDO $con
	 */
	public function postSave(PropelPDO $con = null) { }

	/**
	 * Code to be run before inserting to database
	 * @param PropelPDO $con
	 * @return boolean
	 */
	public function preInsert(PropelPDO $con = null)
	{
		return true;
	}
	
	/**
	 * Code to be run after inserting to database
	 * @param PropelPDO $con 
	 */
	public function postInsert(PropelPDO $con = null) { }

	/**
	 * Code to be run before updating the object in database
	 * @param PropelPDO $con
	 * @return boolean
	 */
	public function preUpdate(PropelPDO $con = null)
	{
		return true;
	}

	/**
	 * Code to be run after updating the object in database
	 * @param PropelPDO $con
	 */
	public function postUpdate(PropelPDO $con = null) { }

	/**
	 * Code to be run before deleting the object in database
	 * @param PropelPDO $con
	 * @return boolean
	 */
	public function preDelete(PropelPDO $con = null)
	{
		return true;
	}

	/**
	 * Code to be run after deleting the object in database
	 * @param PropelPDO $con
	 */
	public function postDelete(PropelPDO $con = null) { }
	
	/**
	 * Sets the modified state for the object to be false.
	 * @param      string $col If supplied, only the specified column is reset.
	 * @return     void
	 */
	public function resetModified($col = null)
	{
		if ($col !== null)
		{
			while (($offset = array_search($col, $this->modifiedColumns)) !== false)
				array_splice($this->modifiedColumns, $offset, 1);
		}
		else
		{
			$this->modifiedColumns = array();
		}
	}

	/**
	 * Compares this with another <code>BaseObject</code> instance.  If
	 * <code>obj</code> is an instance of <code>BaseObject</code>, delegates to
	 * <code>equals(BaseObject)</code>.  Otherwise, returns <code>false</code>.
	 *
	 * @param      obj The object to compare to.
	 * @return     Whether equal to the object specified.
	 */
	public function equals($obj)
	{
		$thisclazz = get_class($this);
		if (is_object($obj) && $obj instanceof $thisclazz) {
			if ($this === $obj) {
				return true;
			} elseif ($this->getPrimaryKey() === null || $obj->getPrimaryKey() === null)  {
				return false;
			} else {
				return ($this->getPrimaryKey() === $obj->getPrimaryKey());
			}
		} else {
			return false;
		}
	}

	/**
	 * If the primary key is not <code>null</code>, return the hashcode of the
	 * primary key.  Otherwise calls <code>Object.hashCode()</code>.
	 *
	 * @return     int Hashcode
	 */
	public function hashCode()
	{
		$ok = $this->getPrimaryKey();
		if ($ok === null) {
			return crc32(serialize($this));
		}
		return crc32(serialize($ok)); // serialize because it could be an array ("ComboKey")
	}

	/**
	 * Logs a message using Propel::log().
	 *
	 * @param      string $msg
	 * @param      int $priority One of the Propel::LOG_* logging levels
	 * @return     boolean
	 */
	protected function log($msg, $priority = Propel::LOG_INFO)
	{
		return Propel::log(get_class($this) . ': ' . $msg, $priority);
	}

}
