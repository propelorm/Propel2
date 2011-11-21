<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Util;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Om\NodeObject;

/**
 * This is a utility interface for all generated NodePeer classes in the system.
 *
 * @author     Heltem <heltem@o2php.com> (Propel)
 * @version    $Revision$
 * @package    propel.runtime.util
 */
interface NodePeer
{
    /**
     * Creates the supplied node as the root node.
     *
     * @param      object $node    Propel object for model
     * @return     object        Inserted propel object for model
     */
    static public function createRoot(NodeObject $node);

    /**
     * Returns the root node for a given scope id
     *
     * @param      int $scopeId        Scope id to determine which root node to return
     * @param      ConnectionInterface $con    Connection to use.
     * @return     object            Propel object for root node
     */
    static public function retrieveRoot($scopeId = 1, ConnectionInterface $con = null);

    /**
     * Inserts $child as first child of destination node $parent
     *
     * @param      object $child    Propel object for child node
     * @param      object $parent    Propel object for parent node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function insertAsFirstChildOf(NodeObject $child, NodeObject $parent, ConnectionInterface $con = null);

    /**
     * Inserts $child as last child of destination node $parent
     *
     * @param      object $child    Propel object for child node
     * @param      object $parent    Propel object for parent node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function insertAsLastChildOf(NodeObject $child, NodeObject $parent, ConnectionInterface $con = null);

    /**
     * Inserts $sibling as previous sibling to destination node $node
     *
     * @param      object $node        Propel object for destination node
     * @param      object $sibling    Propel object for source node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function insertAsPrevSiblingOf(NodeObject $node, NodeObject $sibling, ConnectionInterface $con = null);

    /**
     * Inserts $sibling as next sibling to destination node $node
     *
     * @param      object $node        Propel object for destination node
     * @param      object $sibling    Propel object for source node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function insertAsNextSiblingOf(NodeObject $node, NodeObject $sibling, ConnectionInterface $con = null);

    /**
     * Inserts $parent as parent of given $node.
     *
     * @param      object $parent      Propel object for given parent node
     * @param      object $node      Propel object for given destination node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     * @throws     Exception      When trying to insert node as parent of a root node
     */
    static public function insertAsParentOf(NodeObject $parent, NodeObject $node, ConnectionInterface $con = null);

    /**
     * Inserts $node as root node
     *
     * @param      object $node    Propel object as root node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function insertRoot(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Delete root node
     *
     * @param      int $scopeId        Scope id to determine which root node to delete
     * @param      ConnectionInterface $con    Connection to use.
     * @return     boolean        Deletion status
     */
    static public function deleteRoot($scopeId = 1, ConnectionInterface $con = null);

    /**
     * Delete $dest node
     *
     * @param      object $dest    Propel object node to delete
     * @param      ConnectionInterface $con    Connection to use.
     * @return     boolean        Deletion status
     */
    static public function deleteNode(NodeObject $dest, ConnectionInterface $con = null);

    /**
     * Moves $child to be first child of $parent
     *
     * @param      object $parent    Propel object for parent node
     * @param      object $child    Propel object for child node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function moveToFirstChildOf(NodeObject $parent, NodeObject $child, ConnectionInterface $con = null);

    /**
     * Moves $node to be last child of $dest
     *
     * @param      object $dest    Propel object for destination node
     * @param      object $node    Propel object for source node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function moveToLastChildOf(NodeObject $dest, NodeObject $node, ConnectionInterface $con = null);

    /**
     * Moves $node to be prev sibling to $dest
     *
     * @param      object $dest    Propel object for destination node
     * @param      object $node    Propel object for source node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function moveToPrevSiblingOf(NodeObject $dest, NodeObject $node, ConnectionInterface $con = null);

    /**
     * Moves $node to be next sibling to $dest
     *
     * @param      object $dest    Propel object for destination node
     * @param      object $node    Propel object for source node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     void
     */
    static public function moveToNextSiblingOf(NodeObject $dest, NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets first child for the given node if it exists
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     mixed         Propel object if exists else false
     */
    static public function retrieveFirstChild(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets last child for the given node if it exists
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     mixed         Propel object if exists else false
     */
    static public function retrieveLastChild(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets prev sibling for the given node if it exists
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     mixed         Propel object if exists else false
     */
    static public function retrievePrevSibling(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets next sibling for the given node if it exists
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     mixed         Propel object if exists else false
     */
    static public function retrieveNextSibling(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Retrieves the entire tree from root
     *
     * @param      int $scopeId        Scope id to determine which scope tree to return
     * @param      ConnectionInterface $con    Connection to use.
     */
    static public function retrieveTree($scopeId = 1, ConnectionInterface $con = null);

    /**
     * Retrieves the entire tree from parent $node
     *
     * @param      ConnectionInterface $con    Connection to use.
     */
    static public function retrieveBranch(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets direct children for the node
     *
     * @param      object $node    Propel object for parent node
     * @param      ConnectionInterface $con    Connection to use.
     */
    static public function retrieveChildren(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets all descendants for the node
     *
     * @param      object $node    Propel object for parent node
     * @param      ConnectionInterface $con    Connection to use.
     */
    static public function retrieveDescendants(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets all siblings for the node
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     */
    static public function retrieveSiblings(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets ancestor for the given node if it exists
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     mixed         Propel object if exists else false
     */
    static public function retrieveParent(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets level for the given node
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     int            Level for the given node
     */
    static public function getLevel(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets number of direct children for given node
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     int            Level for the given node
     */
    static public function getNumberOfChildren(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Gets number of descendants for given node
     *
     * @param      object $node    Propel object for src node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     int            Level for the given node
     */
    static public function getNumberOfDescendants(NodeObject $node, ConnectionInterface $con = null);

     /**
     * Returns path to a specific node as an array, useful to create breadcrumbs
     *
     * @param      object $node    Propel object of node to create path to
     * @param      ConnectionInterface $con    Connection to use.
     * @return     array        Array in order of heirarchy
     */
    static public function getPath(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Tests if node is valid
     *
     * @param      object $node    Propel object for src node
     * @return     bool
     */
    static public function isValid(NodeObject $node = null);

    /**
     * Tests if node is a root
     *
     * @param      object $node    Propel object for src node
     * @return     bool
     */
    static public function isRoot(NodeObject $node);

    /**
     * Tests if node is a leaf
     *
     * @param      object $node    Propel object for src node
     * @return     bool
     */
    static public function isLeaf(NodeObject $node);

    /**
     * Tests if $child is a child of $parent
     *
     * @param      object $child    Propel object for node
     * @param      object $parent    Propel object for node
     * @return     bool
     */
    static public function isChildOf(NodeObject $child, NodeObject $parent);

    /**
     * Tests if $node1 is equal to $node2
     *
     * @param      object $node1    Propel object for node
     * @param      object $node2    Propel object for node
     * @return     bool
     */
    static public function isEqualTo(NodeObject $node1, NodeObject $node2);

    /**
     * Tests if $node has an ancestor
     *
     * @param      object $node    Propel object for node
     * @param      ConnectionInterface $con        Connection to use.
     * @return     bool
     */
    static public function hasParent(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Tests if $node has prev sibling
     *
     * @param      object $node    Propel object for node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     bool
     */
    static public function hasPrevSibling(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Tests if $node has next sibling
     *
     * @param      object $node    Propel object for node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     bool
     */
    static public function hasNextSibling(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Tests if $node has children
     *
     * @param      object $node    Propel object for node
     * @return     bool
     */
    static public function hasChildren(NodeObject $node);

    /**
     * Deletes $node and all of its descendants
     *
     * @param      object $node    Propel object for source node
     * @param      ConnectionInterface $con    Connection to use.
     */
    static public function deleteDescendants(NodeObject $node, ConnectionInterface $con = null);

    /**
     * Returns a node given its primary key or the node itself
     *
     * @param      int/object $node    Primary key/instance of required node
     * @param      ConnectionInterface $con    Connection to use.
     * @return     object        Propel object for model
     */
    static public function getNode($node, ConnectionInterface $con = null);

} // NodePeer
