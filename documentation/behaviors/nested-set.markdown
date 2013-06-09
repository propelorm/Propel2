---
layout: documentation
title: NestedSet Behavior
---

# NestedSet Behavior #

The `nested_set` behavior allows a model to become a tree structure, and provides numerous methods to traverse the tree in an efficient way.

Many applications need to store hierarchical data in the model. For instance, a forum stores a tree of messages for each discussion. A CMS sees sections and subsections as a navigation tree. In a business organization chart, each person is a leaf of the organization tree. [Nested sets](http://en.wikipedia.org/wiki/Nested_set_model) are the best way to store such hierarchical data in a relational database and manipulate it. The name "nested sets" describes the algorithm used to store the position of a model in the tree ; it is also known as "modified preorder tree traversal".

## Basic Usage ##

In the `schema.xml`, use the `<behavior>` tag to add the `nested_set` behavior to a table:
```xml
<table name="section">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="nested_set" />
</table>
```

Rebuild your model, insert the table creation sql again, and you're ready to go. The model now has the ability to be inserted into a tree structure, as follows:

```php
<?php
$s1 = new Section();
$s1->setTitle('Home');
$s1->makeRoot(); // make this node the root of the tree
$s1->save();
$s2 = new Section();
$s2->setTitle('World');
$s2->insertAsFirstChildOf($s1); // insert the node in the tree
$s2->save();
$s3 = new Section();
$s3->setTitle('Europe');
$s3->insertAsFirstChildOf($s2); // insert the node in the tree
$s3->save();
$s4 = new Section();
$s4->setTitle('Business');
$s4->insertAsNextSiblingOf($s2); // insert the node in the tree
$s4->save();
/* The sections are now stored in the database as a tree:
    $s1:Home
    |       \
$s2:World  $s4:Business
    |
$s3:Europe
*/
```

You can continue to insert new nodes as children or siblings of existing nodes, using any of the `insertAsFirstChildOf()`, `insertAsLastChildOf()`, `insertAsPrevSiblingOf()`, and `insertAsNextSiblingOf()` methods.

Once you have built a tree, you can traverse it using any of the numerous methods  the `nested_set` behavior adds to the query and model objects. For instance:

```php
<?php
$rootNode = SectionQuery::create()->findRoot(); // $s1
$worldNode = $rootNode->getFirstChild();        // $s2
$businessNode = $worldNode->getNextSibling();   // $s4
$firstLevelSections = $rootNode->getChildren(); // array($s2, $s4)
$allSections = $rootNode->getDescendants();     // array($s2, $s3, $s4)
// you can also chain the methods
$europeNode = $rootNode->getLastChild()->getPrevSibling()->getFirstChild();  // $s3
$path = $europeNode->getAncestors();            // array($s1, $s2)
```

The nodes returned by these methods are regular Propel model objects, with access to the properties and related models. The `nested_set` behavior also adds inspection methods to nodes:

```php
<?php
echo $s2->isRoot();      // false
echo $s2->isLeaf();      // false
echo $s2->getLevel();    // 1
echo $s2->hasChildren(); // true
echo $s2->countChildren(); // 1
echo $s2->hasSiblings(); // true
```

Each of the traversal and inspection methods result in a single database query, whatever the position of the node in the tree. This is because the information about the node position in the tree is stored in three columns of the model, named `tree_left`, `tree_left`, and `tree_level`. The value given to these columns is determined by the nested set algorithm, and it makes read queries much more effective than trees using a simple `parent_id` foreign key.

## Manipulating Nodes ##

You can move a node - and its subtree - across the tree using any of the `moveToFirstChildOf()`, `moveToLastChildOf()`, `moveToPrevSiblingOf()`, and `moveToLastSiblingOf()` methods. These operations are immediate and don't require that you save the model afterwards:

```php
<?php
// move the entire "World" section under "Business"
$s2->moveToFirstChildOf($s4);
/* The tree is modified as follows:
$s1:Home
  |
$s4:Business
  |
$s2:World
  |
$s3:Europe
*/
// now move the "Europe" section directly under root, after "Business"
$s2->moveToFirstChildOf($s4);
/* The tree is modified as follows:
    $s1:Home
    |        \
$s4:Business $s3:Europe
    |
$s2:World
*/
```

You can delete the descendants of a node using `deleteDescendants()`:

```php
<?php
// move the entire "World" section under "Business"
$s4->deleteDescendants($s4);
/* The tree is modified as follows:
    $s1:Home
    |        \
$s4:Business $s3:Europe
*/
```

If you `delete()` a node, all its descendants are deleted in cascade. To avoid accidental deletion of an entire tree, calling `delete()` on a root node throws an exception. Use the `delete()` Query method instead to delete an entire tree.

## Filtering Results ##

The `nested_set` behavior adds numerous methods to the generated Query object. You can use these methods to build more complex queries. For instance, to get all the children of the root node ordered by title, build a Query as follows:

```php
<?php
$children = SectionQuery::create()
  ->childrenOf($rootNode)
  ->orderByTitle()
  ->find();
```

Alternatively, if you already have an existing query method, you can pass it to the model object's methods to filter the results:

```php
<?php
$orderQuery = SectionQuery::create()->orderByTitle();
$children = $rootNode->getChildren($orderQuery);
```

## Multiple Trees ##

When you need to store several trees for a single model - for instance, several threads of posts in a forum - use a _scope_ for each tree. This requires that you enable scope tree support in the behavior definition by setting the `use_scope` parameter to `true`:

```xml
<table name="post">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="body" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="nested_set">
    <parameter name="use_scope" value="true" />
    <parameter name="scope_column" value="thread_id" />
  </behavior>
  <foreign-key foreignTable="thread" onDelete="cascade">
    <reference local="thread_id" foreign="id" />
  </foreign-key>
</table>
```

Now, after rebuilding your model, you can have as many trees as required:

```php
<?php
$thread = ThreadQuery::create()->findPk(123);
$firstPost = PostQuery::create()->findRoot($thread->getId());  // first message of the discussion
$discussion = PostQuery::create()->findTree(thread->getId()); // all messages of the discussion
PostQuery::create()->inTree($thread->getId())->delete(); // delete an entire discussion
$firstPostOfEveryDiscussion = PostQuery::create()->findRoots();
```

## Using a RecursiveIterator ##

An alternative way to browse a tree structure extensively is to use a [RecursiveIterator](http://php.net/RecursiveIterator). The `nested_set` behavior provides an easy way to retrieve such an iterator from a node, and to parse the entire branch in a single iteration.

For instance, to display an entire tree structure, you can use the following code:

```php
<?php
$root = SectionQuery::create()->findRoot();
foreach ($root->getIterator() as $node) {
  echo str_repeat(' ', $node->getLevel()) . $node->getTitle() . "\n";
}
```

The iterator parses the tree in a recursive way by retrieving the children of every node. This can be quite effective on very large trees, since the iterator hydrates only a few objects at a time.

Beware, though, that the iterator executes many queries to parse a tree. On smaller trees, prefer the `getBranch()` method to execute only one query, and hydrate all records at once:

```php
<?php
$root = SectionQuery::create()->findRoot();
foreach ($root->getBranch() as $node) {
  echo str_repeat(' ', $node->getLevel()) . $node->getTitle() . "\n";
}
```

## Parameters ##

By default, the behavior adds three columns to the model - four if you use the scope feature. You can use custom names for the nested sets columns. The following schema illustrates a complete customization of the behavior:

```xml
<table name="post">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="lft" type="INTEGER" />
  <column name="rgt" type="INTEGER" />
  <column name="lvl" type="INTEGER" />
  <column name="thread_id" type="INTEGER" />
  <column name="body" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="nested_set">
    <parameter name="left_column" value="lft" />
    <parameter name="right_column" value="rgt" />
    <parameter name="level_column" value="lvl" />
    <parameter name="use_scope" value="true" />
    <parameter name="scope_column" value="thread_id" />
  </behavior>
  <foreign-key foreignTable="thread" onDelete="cascade">
    <reference local="thread_id" foreign="id" />
  </foreign-key>
</table>
```

Whatever name you give to your columns, the `nested_sets` behavior always adds the following proxy methods, which are mapped to the correct column:

```php
<?php
$post->getLeftValue();         // returns $post->lft
$post->setLeftValue($left);
$post->getRightValue();        // returns $post->rgt
$post->setRightValue($right);
$post->getLevel();             // returns $post->lvl
$post->setLevel($level);
$post->getScopeValue();        // returns $post->thread_id
$post->setScopeValue($scope);
```

If your application used the old nested sets builder from Propel 1.4, you can enable the `method_proxies` parameter so that the behavior generates method proxies for the methods that used a different name (e.g. `createRoot()` for `makeRoot()`, `retrieveFirstChild()` for `getFirstChild()`, etc.

```xml
<table name="section">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="nested_set">
    <parameter name="method_proxies" value="true" />
  </behavior>
</table>
```

## Complete API ##

Here is a list of the methods added by the behavior to the model objects:

```php
<?php
// storage columns accessors
int   getLeftValue()
$node setLeftValue(int $left)
int   getRightValue()
$node setRightValue(int $right)
int   getLevel()
$node setLevel(int $level)
// only for behavior with use_scope
int   getScopeValue()
$node setScopeValue(int $scope)

// root maker (requires calling save() afterwards)
$node makeRoot()

// inspection methods
bool  isInTree()
bool  isRoot()
bool  isLeaf()
bool  isDescendantOf()
bool  isAncestorOf()
bool  hasParent()
bool  hasPrevSibling()
bool  hasNextSibling()
bool  hasChildren()
int   countChildren()
int   countDescendants()

// tree traversal methods
$node getParent()
$node getPrevSibling()
$node getNextSibling()
array getChildren()
$node getFirstChild()
$node getLastChild()
array getSiblings($includeCurrent = false, Criteria $c = null)
array getDescendants(Criteria $c = null)
array getBranch(Criteria $c = null)
array getAncestors(Criteria $c = null)

// node insertion methods (require calling save() afterwards)
$node addChild($node)
$node insertAsFirstChildOf($node)
$node insertAsLastChildOf($node)
$node insertAsPrevSiblingOf($node)
$node insertAsNextSiblingOf($node)

// node move methods (immediate, no need to save() afterwards)
$node moveToFirstChildOf($node)
$node moveToLastChildOf($node)
$node moveToPrevSiblingOf($node)
$node moveToNextSiblingOf($node)

// deletion methods
$node deleteDescendants()

// only for behavior with method_proxies
$node createRoot()
$node retrieveParent()
$node retrievePrevSibling()
$node retrieveNextSibling()
$node retrieveFirstChild()
$node retrieveLastChild()
array getPath()
```

The behavior also adds some methods to the Query classes:

```php
<?php
// tree filter methods
query descendantsOf($node)
query branchOf($node)
query childrenOf($node)
query siblingsOf($node)
query ancestorsOf($node)
query rootsOf($node)
// only for behavior with use_scope
query treeRoots()
query inTree($scope = null)
coll  findRoots()
// order methods
query orderByBranch($reverse = false)
query orderByLevel($reverse = false)
// termination methods
$node findRoot($scope = null)
coll findTree($scope = null)
```

Lastly, the behavior adds a few methods to the Query classes:

```php
<?php
$node retrieveRoot($scope = null)
array retrieveTree($scope = null)
int   deleteTree($scope = null)
// only for behavior with use_scope
array retrieveRoots(Criteria $c = null)
```

## TODO ##

* InsertAsParentOf
