<?php
/*
 *  $Id: BaseObject.php,v 1.4 2005/04/04 11:04:01 dlawson_mi Exp $
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

require_once 'propel/om/Persistent.php';

/**
 * This class contains attributes and methods that are used by all
 * business objects within the system.
 *
 * @author Kaspars Jaudzems <kasparsj@navigators.lv> (Propel)
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @version $Revision: 1.4 $
 */
class BaseObject extends Persistent
{

  /**
  * attribute to determine if this object has previously been saved.
  * @var boolean
  */
  var $_new = true;

  /**
  * attribute to determine whether this object has been deleted.
  * @var boolean
  */
  var $_deleted = false;

  /**
  * The columns that have been modified in current object.
  * Tracking modified columns allows us to only update modified columns.
  * @var array
  */
  var $modifiedColumns = array();


  /**
  * Returns whether the object has been modified.
  *
  * @return boolean True if the object has been modified.
  */
  function isModified()
  {
    return ! empty($this->modifiedColumns);
  }

  /**
  * Has specified column been modified?
  *
  * @param string $col
  * @return boolean True if $col has been modified.
  */
  function isColumnModified($col)
  {
     return in_array($col, $this->modifiedColumns);
  }

  /**
  * Returns whether the object has ever been saved.  This will
  * be false, if the object was retrieved from storage or was created
  * and then saved.
  *
  * @return true, if the object has never been persisted.
  */
  function isNew()
  {
    return $this->_new;
  }

  /**
  * Setter for the isNew attribute.  This method will be called
  * by Propel-generated children and Peers.
  *
  * @param boolean $b the state of the object.
  */
  function setNew($b)
  {
    $this->_new = (boolean) $b;
  }

  /**
  * Whether this object has been deleted.
  * @return boolean The deleted state of this object.
  */
  function isDeleted()
  {
    return $this->_deleted;
  }

  /**
  * Specify whether this object has been deleted.
  * @param boolean $b The deleted state of this object.
  * @return void
  */
  function setDeleted($b)
  {
    $this->_deleted = (boolean) $b;
  }

  /**
  * Sets the modified state for the object to be false.
  * @return void
  */
  function resetModified($col = null)
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
  * @param obj The object to compare to.
  * @return    Whether equal to the object specified.
  */
  function equals($obj)
  {
    if (! is_object($obj)) {
      return false;
    }

    if ($this === $obj) {
      return true;
    }
    else if ($this->getPrimaryKey() === null || $obj->getPrimaryKey() === null)  {
      return false;
    }
    else {
      return ($this->getPrimaryKey() === $obj->getPrimaryKey());
    }
  }

  /**
  * If the primary key is not <code>null</code>, return the hashcode of the
  * primary key.  Otherwise calls <code>Object.hashCode()</code>.
  *
  * @return int Hashcode
  */
  function hashCode()
  {
    $ok = $this->getPrimaryKey();
    if ($ok === null) {
        return crc32(serialize($this));
    }
    return crc32(serialize($ok)); // serialize because it could be an array ("ComboKey")
  }

  /**
  * Logs a message to the Propel::log().
  *
  * @param string $msg
  * @param int $priority
  *
  * @return bool
  */
  function log($msg, $priority = PROPEL_LOG_INFO)
  {
    return Propel::log($msg, $priority);
  }

}
