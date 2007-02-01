<?php

if (!isset($argc)) echo "<pre>";

error_reporting(E_ALL);

$conf_path = realpath(dirname(__FILE__) . '/../projects/treetest/build/conf/treetest-conf.php');
if (!file_exists($conf_path)) {
	echo "Make sure that you specify properties in conf/treetest.properties and "
	."build propel before running this script.";
	exit;
}

// Add PHP_CLASSPATH, if set
if (getenv("PHP_CLASSPATH")) {
	set_include_path(getenv("PHP_CLASSPATH") . PATH_SEPARATOR . get_include_path());
}

 // Add build/classes/ and classes/ to path
set_include_path(
	realpath(dirname(__FILE__) . '/../projects/treetest/build/classes') . PATH_SEPARATOR .
	dirname(__FILE__) . '/../../runtime/classes' . PATH_SEPARATOR .
	get_include_path()
);


// Require classes.
require_once 'propel/Propel.php';
require_once 'treetest/TestNodePeer.php';

function dumpTree($node, $querydb = false, $con = null)
{
	$opts = array('querydb' => $querydb,
				  'con' => $con);

	$node->setIteratorOptions('pre', $opts);

	$indent = 0;
	$lastLevel = $node->getNodeLevel();

	foreach ($node as $n)
	{
		$nodeLevel = $n->getNodeLevel();
		$indent += $nodeLevel - $lastLevel;
		echo str_repeat('  ',  $indent);
		echo $n->getNodePath() . " -> " . $n->getLabel();
		echo "\n";
		$lastLevel = $nodeLevel;
	}
}

try {
	// Initialize Propel
	 Propel::init($conf_path);
} catch (Exception $e) {
	die("Error initializing propel: ". $e->__toString());
}

try {

	$nodeKeySep = TestNodePeer::NPATH_SEP;

	echo "\nCreating initial tree:\n";
	echo "-------------------------------------\n";

	$a = new Test();
	$a->setLabel("a");
	$a = TestNodePeer::createNewRootNode($a);
	echo "Created 'a' as new root\n";

	$b = new TestNode();
	$b->setLabel('b');
	$a->addChildNode($b);
	echo "Added 'b' as first child of 'a'\n";

	$c = new TestNode();
	$c->setLabel('c');
	$a->addChildNode($c);
	echo "Added 'c' as second child of 'a'\n";

	$f = new TestNode();
	$f->setLabel('f');
	$b->addChildNode($f);
	echo "Added 'f' as first child of 'b'\n";

	$d = new TestNode();
	$d->setLabel('d');
	$b->addChildNode($d, $f);
	echo "Added 'd' as first child of 'b' before 'f' (insert before first child test - f is now second child)\n";

	$e = new TestNode();
	$e->setLabel('e');
	$b->addChildNode($e, $f);
	echo "Added 'e' as second child of 'b' before 'f' (insert before last child test - f is now third child)\n";

	$g = new TestNode();
	$g->setLabel('g');
	$c->addChildNode($g);
	echo "Added 'g' as first child of 'c'\n";

	$h = new TestNode();
	$h->setLabel('h');
	$c->addChildNode($h);
	echo "Added 'h' as second child of 'c'\n";

	$i = new TestNode();
	$i->setLabel('i');
	$d->addChildNode($i);
	echo "Added 'i' as first child of 'd'\n";

	$j = new TestNode();
	$j->setLabel('j');
	$f->addChildNode($j);
	echo "Added 'j' as first child of 'f'\n";

	$k = new TestNode();
	$k->setLabel('k');
	$j->addChildNode($k);
	echo "Added 'k' as first child of 'j'\n";

	$l = new TestNode();
	$l->setLabel('l');
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
	$b->addChildNode($j, $e);
	dumpTree($a);

	echo "\nMove 'j' sub-tree to 'c' (move tree after last child test):\n";
	$c->addChildNode($j);
	dumpTree($a);

	echo "\nMove 'j' sub-tree to 'g' (move tree to first child test):\n";
	$g->addChildNode($j);
	dumpTree($a);


	echo "\n\nCreating new (in-memory) sub-tree:\n";
	echo "-------------------------------------\n";

	$m = new TestNode();
	$m->setLabel('m');
	echo "Created 'm' as root of new sub-tree\n";

	$n = new TestNode();
	$n->setLabel('n');
	$m->addChildNode($n);
	echo "Added 'n' as first child of 'm'\n";

	$o = new TestNode();
	$o->setLabel('o');
	$m->addChildNode($o);
	echo "Added 'o' as second child of 'm'\n";

	$r = new TestNode();
	$r->setLabel('r');
	$n->addChildNode($r);
	echo "Added 'r' as first child of 'n'\n";

	$p = new TestNode();
	$p->setLabel('p');
	$n->addChildNode($p, $r);
	echo "Added 'p' as first child of 'n' before 'r' (insert before first child test - r is now second child)\n";

	$q = new TestNode();
	$q->setLabel('q');
	$n->addChildNode($q, $r);
	echo "Added 'q' as second child of 'n' before 'r' (insert before last child test - r is now third child)\n";

	$s = new TestNode();
	$s->setLabel('s');
	$o->addChildNode($s);
	echo "Added 's' as first child of 'o'\n";

	$t = new TestNode();
	$t->setLabel('t');
	$o->addChildNode($t);
	echo "Added 't' as second child of 'o'\n";

	$u = new TestNode();
	$u->setLabel('u');
	$p->addChildNode($u);
	echo "Added 'u' as first child of 'p'\n";

	$v = new TestNode();
	$v->setLabel('v');
	$r->addChildNode($v);
	echo "Added 'v' as first child of 'r'\n";

	$w = new TestNode();
	$w->setLabel('w');
	$v->addChildNode($w);
	echo "Added 'w' as first child of 'v'\n";

	$x = new TestNode();
	$x->setLabel('x');
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
	$n->addChildNode($v, $q);
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
	$z = new Test();
	$z->setLabel("z");
	$z = TestNodePeer::insertNewRootNode($z);

	dumpTree($z, true);

} catch (Exception $e) {
	die("Error creating initial tree: " . $e->__toString());
}

try {

	echo "\n\nTest retrieveRootNode() (without descendants)\n";
	echo "-------------------------------------\n";
	$root = TestNodePeer::retrieveRootNode(false);
	dumpTree($root);


	echo "\n\nTest retrieveRootNode() (with descendants)\n";
	echo "-------------------------------------\n";
	$root = TestNodePeer::retrieveRootNode(true);
	dumpTree($root);

	$m_addr = array(1,1,3);

	echo "\n\nTest retrieveNodeByNP() for 'm' (without descendants)\n";
	echo "-------------------------------------\n";
	$node = TestNodePeer::retrieveNodeByNP(implode($nodeKeySep, $m_addr), false, false);
	dumpTree($node);


	echo "\n\nTest retrieveNodeByNP() for 'm' (with descendants)\n";
	echo "-------------------------------------\n";
	$node = TestNodePeer::retrieveNodeByNP(implode($nodeKeySep, $m_addr), false, true);
	dumpTree($node);


	echo "\n\nTest getAncestors() for 'x' in one query:\n";
	echo "-------------------------------------\n";

	$criteria = new Criteria();
	$criteria->add(TestPeer::LABEL, 'x', Criteria::EQUAL);

	$nodes = TestNodePeer::retrieveNodes($criteria, true, false);
	$ancestors = $nodes[0]->getAncestors(false);

	foreach ($ancestors as $ancestor)
		echo $ancestor->getNodePath() . " -> " . $ancestor->getLabel() . "\n";


	echo "\n\nTest retrieveNodeByNP() for 'o' (with ancestors and descendants in one query):\n";
	echo "-------------------------------------\n";

	$o_addr = array(1,1,3,2);

	$node = TestNodePeer::retrieveNodeByNP(implode($nodeKeySep, $o_addr), true, true);

	echo "ancestors:\n";
	foreach ($node->getAncestors(false) as $ancestor)
		echo $ancestor->getNodePath() . " -> " . $ancestor->getLabel() . "\n";

	echo "\ndescendants:\n";
	dumpTree($node);


	echo "\n\nTest retrieveNodes() between 'b' and 'g' (without descendants)\n";
	echo "-------------------------------------\n";

	$criteria = new Criteria();
	$criteria->add(TestPeer::LABEL, 'b', Criteria::GREATER_EQUAL);
	$criteria->addAnd(TestPeer::LABEL, 'g', Criteria::LESS_EQUAL);
	$criteria->addAscendingOrderByColumn(TestPeer::LABEL);

	$nodes = TestNodePeer::retrieveNodes($criteria, false, false);

	foreach ($nodes as $node)
		echo $node->getNodePath() . " -> " . $node->getLabel() . "\n";


	echo "\n\nTest retrieveNodes() between 'b' and 'g' (with descendants)\n";
	echo "-------------------------------------\n";

	$criteria = new Criteria();
	$criteria->add(TestPeer::LABEL, 'b', Criteria::GREATER_EQUAL);
	$criteria->addAnd(TestPeer::LABEL, 'g', Criteria::LESS_EQUAL);
	$criteria->addAscendingOrderByColumn(TestPeer::LABEL);

	$nodes = TestNodePeer::retrieveNodes($criteria, false, true);

	foreach ($nodes as $node)
	{
		dumpTree($node);
		echo "\n";
	}


} catch (Exception $e) {
	die("Error retrieving nodes: " . $e->__toString());
}

try {

	echo "\nCreating new tree:\n";
	echo "-------------------------------------\n";

	$a = new Test();
	$a->setLabel("a");
	$a = TestNodePeer::createNewRootNode($a);
	echo "Created 'a' as new root\n";

	echo "\nAdding 10 child nodes:\n";
	echo "-------------------------------------\n";

	$b = new TestNode();
	$b->setLabel('b');
	$a->addChildNode($b);

	$c = new TestNode();
	$c->setLabel('c');
	$a->addChildNode($c);

	$d = new TestNode();
	$d->setLabel('d');
	$a->addChildNode($d);

	$e = new TestNode();
	$e->setLabel('e');
	$a->addChildNode($e);

	$f = new TestNode();
	$f->setLabel('f');
	$a->addChildNode($f);

	$g = new TestNode();
	$g->setLabel('g');
	$a->addChildNode($g);

	$h = new TestNode();
	$h->setLabel('h');
	$a->addChildNode($h);

	$i = new TestNode();
	$i->setLabel('i');
	$a->addChildNode($i);

	$j = new TestNode();
	$j->setLabel('j');
	$a->addChildNode($j);

	$k = new TestNode();
	$k->setLabel('k');
	$a->addChildNode($k);

	echo "\ndescendants:\n";
	dumpTree($a);

	echo "\nRetrieving last node:\n";
	echo "-------------------------------------\n";

	$last = $a->getLastChildNode(true);
	echo "Last child node is '" . $last->getLabel() . "' (" . $last->getNodePath() . ")\n";

} catch (Exception $e) {
	die("Error creating tree with > 10 nodes: " . $e->__toString());
}

if (!isset($argc)) echo "</pre>";
