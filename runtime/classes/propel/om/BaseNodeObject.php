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
 * This interface defines methods that must be implemented by all
 * business objects within the system to handle Node object.
 *
 * @author     Heltem <heltme@o2php.com> (Propel)
 * @version    $Revision$
 * @package    propel.om
 */
interface BaseNodeObject extends IteratorAggregate {
	/**
	 * If object is saved without left/right values, set them as undefined (0)
	 * @param      PropelPDO $con		Connection to use.
	 */
	public function save(PropelPDO $con = null);

	/**
	 * Delete node and descendants
	 * @param      PropelPDO $con		Connection to use.
	 */
	public function delete(PropelPDO $con = null);

	/**
	 * Gets the level if set, otherwise calculates this and returns it
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     int
	 */
	public function getLevel(PropelPDO $con = null);

	/**
	 * Get the path to the node in the tree
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     array
	 */
	public function getPath(PropelPDO $con = null);

	/**
	 * Gets the number of children for the node (direct descendants)
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     int
	 */
	public function getNumberOfChildren(PropelPDO $con = null);

	/**
	 * Gets the total number of desceandants for the node
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     int
	 */
	public function getNumberOfDescendants(PropelPDO $con = null);

	/**
	 * Gets the children for the node
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     array
	 */
	public function getChildren(PropelPDO $con = null);

	/**
	 * Gets the descendants for the node
	 *
	 * @param      PropelPDO $con		Connection to use.
 	 * @return     array
	 */
	public function getDescendants(PropelPDO $con = null);

	/**
	 * Determines if the node is the root node
	 *
	 * @return     bool
	 */
	public function isRoot();

	/**
	 * Determines if the node is a leaf node
	 *
	 * @return     bool
	 */
	public function isLeaf();

	/**
	 * Tests if object is equal to $node
	 *
	 * @param      object $node	Propel object for node to compare to
	 * @return     bool
	 */
	public function isEqualTo(BaseNodeObject $node = null);

	/**
	 * Tests if object has an ancestor
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     bool
	 */
	public function hasParent(PropelPDO $con = null);

	/**
	 * Determines if the node has children / descendants
	 *
	 * @return     bool
	 */
	public function hasChildren();

	/**
	 * Determines if the node has previous sibling
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     bool
	 */
	public function hasPrevSibling(PropelPDO $con = null);

	/**
	 * Determines if the node has next sibling
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     bool
	 */
	public function hasNextSibling(PropelPDO $con = null);

	/**
	 * Gets ancestor for the given node if it exists
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrieveParent(PropelPDO $con = null);

	/**
	 * Gets first child if it exists
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrieveFirstChild(PropelPDO $con = null);

	/**
	 * Gets last child if it exists
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrieveLastChild(PropelPDO $con = null);

	/**
	 * Gets prev sibling for the given node if it exists
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrievePrevSibling(PropelPDO $con = null);

	/**
	 * Gets next sibling for the given node if it exists
	 *
	 * @param      PropelPDO $con		Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrieveNextSibling(PropelPDO $con = null);

	/**
	 * Inserts as first child of destination node $dest
	 *
	 * @param      object $dest	Propel object for destination node
	 * @param      PropelPDO $con		Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public function insertAsFirstChildOf(BaseNodeObject $dest = null, PropelPDO $con = null);

	/**
	 * Inserts as last child of destination node $dest
	 *
	 * @param      object $dest	Propel object for destination node
	 * @param      PropelPDO $con		Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public function insertAsLastChildOf(BaseNodeObject $dest = null, PropelPDO $con = null);

	/**
	 * Inserts $node as previous sibling to destination node $dest
	 *
	 * @param      object $dest	Propel object for destination node
	 * @param      PropelPDO $con		Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public function insertAsPrevSiblingOf(BaseNodeObject $dest = null, PropelPDO $con = null);

	/**
	 * Inserts $node as next sibling to destination node $dest
	 *
	 * @param      object $dest	Propel object for destination node
	 * @param      PropelPDO $con		Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public function insertAsNextSiblingOf(BaseNodeObject $dest = null, PropelPDO $con = null);

} // BaseNodeObject
