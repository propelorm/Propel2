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
 * This interface defines methods related to saving an object
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @author     Fedor K. <fedor@apache.org> (Torque)
 * @version    $Revision$
 * @package    propel.om
 */
interface Persistent {

	/**
	 * getter for the object primaryKey.
	 *
	 * @return     ObjectKey the object primaryKey as an Object
	 */
	public function getPrimaryKey();

	/**
	 * Sets the PrimaryKey for the object.
	 *
	 * @param      mixed $primaryKey The new PrimaryKey object or string (result of PrimaryKey.toString()).
	 * @return     void
	 * @throws     Exception, This method might throw an exceptions
	 */
	public function setPrimaryKey($primaryKey);


	/**
	 * Returns whether the object has been modified, since it was
	 * last retrieved from storage.
	 *
	 * @return     boolean True if the object has been modified.
	 */
	public function isModified();

	/**
	 * Has specified column been modified?
	 *
	 * @param      string $col
	 * @return     boolean True if $col has been modified.
	 */
	public function isColumnModified($col);

	/**
	 * Returns whether the object has ever been saved.  This will
	 * be false, if the object was retrieved from storage or was created
	 * and then saved.
	 *
	 * @return     boolean True, if the object has never been persisted.
	 */
	public function isNew();

	/**
	 * Setter for the isNew attribute.  This method will be called
	 * by Propel-generated children and Peers.
	 *
	 * @param      boolean $b the state of the object.
	 */
	public function setNew($b);

	/**
	 * Resets (to false) the "modified" state for this object.
	 *
	 * @return     void
	 */
	public function resetModified();

	/**
	 * Whether this object has been deleted.
	 * @return     boolean The deleted state of this object.
	 */
	public function isDeleted();

	/**
	 * Specify whether this object has been deleted.
	 * @param      boolean $b The deleted state of this object.
	 * @return     void
	 */
	public function setDeleted($b);

	/**
	 * Deletes the object.
	 * @param      PropelPDO $con
	 * @return     void
	 * @throws     Exception
	 */
	public function delete(PropelPDO $con = null);

	/**
	 * Saves the object.
	 * @param      PropelPDO $con
	 * @return     void
	 * @throws     Exception
	 */
	public function save(PropelPDO $con = null);
}
