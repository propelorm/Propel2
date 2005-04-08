<?php

if (!isset($argc)) echo "<pre>";

error_reporting(E_ALL);

//
// ROOT_DIR
// |-- creole
//   |-- creole-php4
// |-- propel
//   |-- propel-generator
//   |-- propel-php4
//
define('ROOT_DIR',      realpath(dirname(__FILE__) . '/../../../../') . '/');
define('CREOLE_DIR',    ROOT_DIR . 'creole/');
define('PROPEL_DIR',    ROOT_DIR . 'propel/');
define('TREETEST_DIR',  PROPEL_DIR . 'propel-generator/projects/treetest/');
define('TREETEST_CONF', TREETEST_DIR . 'build/conf/treetest-conf.php');

$includes = array();
$includes[] = getenv('PHP_CLASSPATH');
$includes[] = CREOLE_DIR . 'creole-php4/classes/';
$includes[] = PROPEL_DIR . 'propel-php4/classes/';
$includes[] = TREETEST_DIR . 'build/classes/';
$includes[] = ini_get('include_path');
$includes = implode(PATH_SEPARATOR, $includes);

ini_set('include_path', $includes);


if (! is_readable(TREETEST_CONF)) {
    echo "Make sure that you specify properties in conf/treetest.properties and "
    ."build propel before running this script.";
    exit;
}

// Require classes.
require_once 'propel/Propel.php';
require_once 'treetest/TestNodePeer.php';

function dumpTree(&$node, $querydb = false)
{
    $opts = array();
	$opts['querydb'] = $querydb;

    $node->setIteratorOptions('pre', $opts);

    $indent = 0;
    $lastLevel = $node->getNodeLevel();

	for ($it =& $node->getIterator(); $it->valid(); $it->next()) {

		$n =& $it->current();
        $nodeLevel = $n->getNodeLevel();
        $indent += $nodeLevel - $lastLevel;
        echo str_repeat('  ',  $indent);
        echo $n->getNodePath() . " -> " . $n->callObjMethod('getLabel');
        echo "\n";
        $lastLevel = $nodeLevel;
	}
}

Propel::init(TREETEST_CONF);

$nodeKeySep = TestNodePeer::NPATH_SEP();

echo "\nCreating initial tree:\n";
echo "-------------------------------------\n";

// Let`s create a connection object
// and pass it to createNewRootNode method
// just for testing how the optional parameters work
$con =& Propel::getConnection(TestPeer::DATABASE_NAME());

$a =& new Test();
$a->setLabel("a");
$a =& TestNodePeer::createNewRootNode($a);
if (Propel::isError($a)) { die($a->getMessage() . "\n"); }
echo "Created 'a' as new root\n";

$b =& new TestNode();

//This is the most accurate way to call methods on node object
$b_obj =& $b->getNodeObj();
$b_obj->setLabel('b');

$a->addChildNode($b);
echo "Added 'b' as first child of 'a'\n";

$c =& new TestNode();

//This is an alternative way
//The array is there only for testing purposes
//actually this only sets label to c,
//and the 'test' string is discarded
$c->callObjMethod('setLabel', array('c', 'test'));

$a->addChildNode($c);
echo "Added 'c' as second child of 'a'\n";

$f =& new TestNode();
$f->callObjMethod('setLabel', 'f');
$b->addChildNode($f);
echo "Added 'f' as first child of 'b'\n";

$d =& new TestNode();
$d->callObjMethod('setLabel', 'd');
$b->addChildNode($d, Param::set($f));
echo "Added 'd' as first child of 'b' before 'f' (insert before first child test - f is now second child)\n";

$e =& new TestNode();
$e->callObjMethod('setLabel', 'e');
$b->addChildNode($e, Param::set($f));
echo "Added 'e' as second child of 'b' before 'f' (insert before last child test - f is now third child)\n";

$g =& new TestNode();
$g->callObjMethod('setLabel', 'g');
$c->addChildNode($g);
echo "Added 'g' as first child of 'c'\n";

$h =& new TestNode();
$h->callObjMethod('setLabel', 'h');
$c->addChildNode($h);
echo "Added 'h' as second child of 'c'\n";

$i =& new TestNode();
$i->callObjMethod('setLabel', 'i');
$d->addChildNode($i);
echo "Added 'i' as first child of 'd'\n";

$j =& new TestNode();
$j->callObjMethod('setLabel', 'j');
$f->addChildNode($j);
echo "Added 'j' as first child of 'f'\n";

$k =& new TestNode();
$k->callObjMethod('setLabel', 'k');
$j->addChildNode($k);
echo "Added 'k' as first child of 'j'\n";

$l =& new TestNode();
$l->callObjMethod('setLabel', 'l');
$j->addChildNode($l);
echo "Added 'l' as second child of 'j'\n";

dumpTree($a);

echo "\n\nDeleting 'd' node sub-tree:\n";
echo "-------------------------------------\n";

$d->delete();
dumpTree($a);

echo "\n\nMove node tests:\n";
echo "-------------------------------------\n";

echo "Move 'j' sub-tree to 'b' before 'e' (move tree/insert before first child test):\n";
$b->addChildNode($j, Param::set($e));
dumpTree($a);

echo "\nMove 'j' sub-tree to 'c' (move tree after last child test):\n";
$c->addChildNode($j);
dumpTree($a);

echo "\nMove 'j' sub-tree to 'g' (move tree to first child test):\n";
$g->addChildNode($j);
dumpTree($a);


echo "\n\nCreating new (in-memory) sub-tree:\n";
echo "-------------------------------------\n";

$m =& new TestNode();
$m->callObjMethod('setLabel', 'm');
echo "Created 'm' as root of new sub-tree\n";

$n =& new TestNode();
$n->callObjMethod('setLabel', 'n');
$m->addChildNode($n);
echo "Added 'n' as first child of 'm'\n";

$o =& new TestNode();
$o->callObjMethod('setLabel', 'o');
$m->addChildNode($o);
echo "Added 'o' as second child of 'm'\n";

$r =& new TestNode();
$r->callObjMethod('setLabel', 'r');
$n->addChildNode($r);
echo "Added 'r' as first child of 'n'\n";

$p =& new TestNode();
$p->callObjMethod('setLabel', 'p');
$n->addChildNode($p, Param::set($r));
echo "Added 'p' as first child of 'n' before 'r' (insert before first child test - r is now second child)\n";

$q =& new TestNode();
$q->callObjMethod('setLabel', 'q');
$n->addChildNode($q, Param::set($r));
echo "Added 'q' as second child of 'n' before 'r' (insert before last child test - r is now third child)\n";

$s =& new TestNode();
$s->callObjMethod('setLabel', 's');
$o->addChildNode($s);
echo "Added 's' as first child of 'o'\n";

$t =& new TestNode();
$t->callObjMethod('setLabel', 't');
$o->addChildNode($t);
echo "Added 't' as second child of 'o'\n";

$u =& new TestNode();
$u->callObjMethod('setLabel', 'u');
$p->addChildNode($u);
echo "Added 'u' as first child of 'p'\n";

$v =& new TestNode();
$v->callObjMethod('setLabel', 'v');
$r->addChildNode($v);
echo "Added 'v' as first child of 'r'\n";

$w =& new TestNode();
$w->callObjMethod('setLabel', 'w');
$v->addChildNode($w);
echo "Added 'w' as first child of 'v'\n";

$x =& new TestNode();
$x->callObjMethod('setLabel', 'x');
$v->addChildNode($x);
echo "Added 'x' as second child of 'v'\n";

dumpTree($m);

echo "\n\nDeleting in-memory 'p' node sub-tree:\n";
echo "-------------------------------------\n";

$p->delete();

dumpTree($m);


echo "\n\nMove in-memory node tests:\n";
echo "-------------------------------------\n";

echo "Move 'v' sub-tree to 'n' before 'q' (move tree/insert before first child test):\n";
$n->addChildNode($v, Param::set($q));
dumpTree($m);

echo "\nMove 'v' sub-tree to 'o' (move tree after last child test):\n";
$o->addChildNode($v);
dumpTree($m);

echo "\nMove 'v' sub-tree to 's' (move tree to first child test):\n";
$s->addChildNode($v);
dumpTree($m);

echo "\n\nAdd in-memory 'm' sub-tree to 'a':\n";
echo "-------------------------------------\n";

$a->addChildNode($m);
dumpTree($a);

echo "\n\nInsert new root node 'z' and retrieve descendants on demand (via querydb param in iterator):\n";
echo "-------------------------------------\n";
$z =& new Test();
$z->setLabel("z");
$z =& TestNodePeer::insertNewRootNode($z);

dumpTree($z, true);

echo "\n\nTest retrieveRootNode() (without descendants)\n";
echo "-------------------------------------\n";
$root =& TestNodePeer::retrieveRootNode(false);
dumpTree($root);


echo "\n\nTest retrieveRootNode() (with descendants)\n";
echo "-------------------------------------\n";
$root =& TestNodePeer::retrieveRootNode(true);
dumpTree($root);

$m_addr = array(1,1,3);

echo "\n\nTest retrieveNodeByNP() for 'm' (without descendants)\n";
echo "-------------------------------------\n";
$node =& TestNodePeer::retrieveNodeByNP(implode($nodeKeySep, $m_addr), false, false);
dumpTree($node);

echo "\n\nTest retrieveNodeByNP() for 'm' (with descendants)\n";
echo "-------------------------------------\n";
$node =& TestNodePeer::retrieveNodeByNP(implode($nodeKeySep, $m_addr), false, true);
dumpTree($node);

echo "\n\nTest getAncestors() for 'x' in one query:\n";
echo "-------------------------------------\n";

$criteria =& new Criteria();
$criteria->add(TestPeer::LABEL(), 'x', Criteria::EQUAL());

$nodes =& TestNodePeer::retrieveNodes($criteria, true, false);
$ancestors =& $nodes[0]->getAncestors(false);

for($i = 0; $i < count($ancestors); $i++)
	echo $ancestors[$i]->getNodePath() . " -> " . $ancestors[$i]->callObjMethod('getLabel') . "\n";

$o_addr = array(1,1,3,2);

echo "\n\nTest retrieveNodeByNP() for 'o' (with ancestors and descendants in one query):\n";
echo "-------------------------------------\n";

$node =& TestNodePeer::retrieveNodeByNP(implode($nodeKeySep, $o_addr), true, true);

echo "ancestors:\n";
$ancestors =& $node->getAncestors(false);
for ($i = 0; $i < count($ancestors); $i++)
    echo $ancestors[$i]->getNodePath() . " -> " . $ancestors[$i]->callObjMethod('getLabel') . "\n";

echo "\ndescendants:\n";
dumpTree($node);
echo "\n\nTest retrieveNodes() between 'b' and 'g' (without descendants)\n";
echo "-------------------------------------\n";

$criteria = new Criteria();
$criteria->add(TestPeer::LABEL(), 'b', Criteria::GREATER_EQUAL());
$criteria->addAnd(TestPeer::LABEL(), 'g', Criteria::LESS_EQUAL());
$criteria->addAscendingOrderByColumn(TestPeer::LABEL());

$nodes = TestNodePeer::retrieveNodes($criteria, false, false);

foreach ($nodes as $node)
	echo $node->getNodePath() . " -> " . $node->callObjMethod('getLabel') . "\n";

echo "\n\nTest retrieveNodes() between 'b' and 'g' (with descendants)\n";
echo "-------------------------------------\n";

$criteria = new Criteria();
$criteria->add(TestPeer::LABEL(), 'b', Criteria::GREATER_EQUAL());
$criteria->addAnd(TestPeer::LABEL(), 'g', Criteria::LESS_EQUAL());
$criteria->addAscendingOrderByColumn(TestPeer::LABEL());

$nodes = TestNodePeer::retrieveNodes($criteria, false, true);

foreach ($nodes as $node)
{
	dumpTree($node);
	echo "\n";
}

if (!isset($argc)) echo "</pre>";

?>
