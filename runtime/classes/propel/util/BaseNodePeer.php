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
 * This is a utility interface for all generated NodePeer classes in the system.
 *
 * @author     Heltem <heltem@o2php.com> (Propel)
 * @version    $Revision$
 * @package    propel.util
 */
interface BaseNodePeer {
	/**
	 * Creates the supplied node as the root node.
	 *
	 * @param      object $node	Propel object for model
	 * @return     object		Inserted propel object for model
	 */
	public static function createRoot(BaseNodeObject $node);

	/**
	 * Returns the root node for a given scope id
	 *
	 * @param      int $scopeId		Scope id to determine which root node to return
	 * @param      PropelPDO $con	Connection to use.
	 * @return     object			Propel object for root node
	 */
	public static function retrieveRoot($scopeId = 1, PropelPDO $con = null);

	/**
	 * Inserts $child as first child of destination node $parent
	 *
	 * @param      object $child	Propel object for child node
	 * @param      object $parent	Propel object for parent node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function insertAsFirstChildOf(BaseNodeObject $child, BaseNodeObject $parent, PropelPDO $con = null);

	/**
	 * Inserts $child as last child of destination node $parent
	 *
	 * @param      object $child	Propel object for child node
	 * @param      object $parent	Propel object for parent node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function insertAsLastChildOf(BaseNodeObject $child, BaseNodeObject $parent, PropelPDO $con = null);

	/**
	 * Inserts $sibling as previous sibling to destination node $node
	 *
	 * @param      object $node		Propel object for destination node
	 * @param      object $sibling	Propel object for source node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function insertAsPrevSiblingOf(BaseNodeObject $node, BaseNodeObject $sibling, PropelPDO $con = null);

	/**
	 * Inserts $sibling as next sibling to destination node $node
	 *
	 * @param      object $node		Propel object for destination node
	 * @param      object $sibling	Propel object for source node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function insertAsNextSiblingOf(BaseNodeObject $node, BaseNodeObject $sibling, PropelPDO $con = null);

	/**
	 * Inserts $node as root node
	 *
	 * @param      object $node	Propel object as root node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public static function insertRoot(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Inserts $parent as parent to destination node $child
	 *
	 * @param      object $child	Propel object to become child node
	 * @param      object $parent	Propel object as parent node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function insertParent(BaseNodeObject $child, BaseNodeObject $parent, PropelPDO $con = null);

	/**
	 * Delete root node
	 *
	 * @param      int $scopeId		Scope id to determine which root node to delete
	 * @param      PropelPDO $con	Connection to use.
	 * @return     boolean		Deletion status
	 */
	public static function deleteRoot($scopeId = 1, PropelPDO $con = null);

	/**
	 * Delete $dest node
	 *
	 * @param      object $dest	Propel object node to delete
	 * @param      PropelPDO $con	Connection to use.
	 * @return     boolean		Deletion status
	 */
	public static function deleteNode(BaseNodeObject $dest, PropelPDO $con = null);

	/**
	 * Moves $child to be first child of $parent
	 *
	 * @param      object $parent	Propel object for parent node
	 * @param      object $child	Propel object for child node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function moveToFirstChildOf(BaseNodeObject $parent, BaseNodeObject $child, PropelPDO $con = null);

	/**
	 * Moves $node to be last child of $dest
	 *
	 * @param      object $dest	Propel object for destination node
	 * @param      object $node	Propel object for source node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function moveToLastChildOf(BaseNodeObject $dest, BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Moves $node to be prev sibling to $dest
	 *
	 * @param      object $dest	Propel object for destination node
	 * @param      object $node	Propel object for source node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function moveToPrevSiblingOf(BaseNodeObject $dest, BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Moves $node to be next sibling to $dest
	 *
	 * @param      object $dest	Propel object for destination node
	 * @param      object $node	Propel object for source node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     void
	 */
	public static function moveToNextSiblingOf(BaseNodeObject $dest, BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets first child for the given node if it exists
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public static function retrieveFirstChild(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets last child for the given node if it exists
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public static function retrieveLastChild(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets prev sibling for the given node if it exists
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public static function retrievePrevSibling(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets next sibling for the given node if it exists
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public static function retrieveNextSibling(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Retrieves the entire tree from root
	 *
	 * @param      int $scopeId		Scope id to determine which scope tree to return
	 * @param      PropelPDO $con	Connection to use.
	 */
	public static function retrieveTree($scopeId = 1, PropelPDO $con = null);

	/**
	 * Retrieves the entire tree from parent $node
	 *
	 * @param      PropelPDO $con	Connection to use.
	 */
	public static function retrieveBranch(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets direct children for the node
	 *
	 * @param      object $node	Propel object for parent node
	 * @param      PropelPDO $con	Connection to use.
	 */
	public static function retrieveChildren(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets all descendants for the node
	 *
	 * @param      object $node	Propel object for parent node
	 * @param      PropelPDO $con	Connection to use.
	 */
	public static function retrieveDescendants(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets all siblings for the node
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 */
	public static function retrieveSiblings(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets ancestor for the given node if it exists
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public static function retrieveParent(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets level for the given node
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     int			Level for the given node
	 */
	public static function getLevel(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets number of direct children for given node
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     int			Level for the given node
	 */
	public static function getNumberOfChildren(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Gets number of descendants for given node
	 *
	 * @param      object $node	Propel object for src node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     int			Level for the given node
	 */
	public static function getNumberOfDescendants(BaseNodeObject $node, PropelPDO $con = null);

 	/**
	 * Returns path to a specific node as an array, useful to create breadcrumbs
	 *
	 * @param      object $node	Propel object of node to create path to
	 * @param      PropelPDO $con	Connection to use.
	 * @return     array		Array in order of heirarchy
	 */
	public static function getPath(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Tests if node is valid
	 *
	 * @param      object $node	Propel object for src node
	 * @return     bool
	 */
	public static function isValid(BaseNodeObject $node = null);

	/**
	 * Tests if node is a root
	 *
	 * @param      object $node	Propel object for src node
	 * @return     bool
	 */
	public static function isRoot(BaseNodeObject $node);

	/**
	 * Tests if node is a leaf
	 *
	 * @param      object $node	Propel object for src node
	 * @return     bool
	 */
	public static function isLeaf(BaseNodeObject $node);

	/**
	 * Tests if $child is a child of $parent
	 *
	 * @param      object $child	Propel object for node
	 * @param      object $parent	Propel object for node
	 * @return     bool
	 */
	public static function isChildOf(BaseNodeObject $child, BaseNodeObject $parent);

	/**
	 * Tests if $node1 is a child of or equal to $node2
	 *
	 * @param      object $node1	Propel object for node
	 * @param      object $node2	Propel object for node
	 * @param      PropelPDO $con		Connection to use.
	 * @return     bool
	 */
	static function isChildOfOrSiblingTo(BaseNodeObject $node1, BaseNodeObject $node2);

	/**
	 * Tests if $node1 is equal to $node2
	 *
	 * @param      object $node1	Propel object for node
	 * @param      object $node2	Propel object for node
	 * @return     bool
	 */
	public static function isEqualTo(BaseNodeObject $node1, BaseNodeObject $node2);

	/**
	 * Tests if $node has an ancestor
	 *
	 * @param      object $node	Propel object for node
	 * @param      PropelPDO $con		Connection to use.
	 * @return     bool
	 */
	public static function hasParent(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Tests if $node has prev sibling
	 *
	 * @param      object $node	Propel object for node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     bool
	 */
	public static function hasPrevSibling(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Tests if $node has next sibling
	 *
	 * @param      object $node	Propel object for node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     bool
	 */
	public static function hasNextSibling(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Tests if $node has children
	 *
	 * @param      object $node	Propel object for node
	 * @return     bool
	 */
	public static function hasChildren(BaseNodeObject $node);

	/**
	 * Deletes $node and all of its descendants
	 *
	 * @param      object $node	Propel object for source node
	 * @param      PropelPDO $con	Connection to use.
	 */
	public static function deleteDescendants(BaseNodeObject $node, PropelPDO $con = null);

	/**
	 * Returns a node given its primary key or the node itself
	 *
	 * @param      int/object $node	Primary key/instance of required node
	 * @param      PropelPDO $con	Connection to use.
	 * @return     object		Propel object for model
	 */
	public static function getNode($node, PropelPDO $con = null);

} // BaseNodePeer
