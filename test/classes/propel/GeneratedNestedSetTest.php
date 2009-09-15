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

require_once 'cms/CmsTestBase.php';

/**
 * Tests the generated nested-set Object classes.
 *
 * This test uses generated Bookstore-Cms classes to test the behavior of various
 * object operations.  The _idea_ here is to test every possible generated method
 * from Object.tpl; if necessary, bookstore will be expanded to accommodate this.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the CmsDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        CmsDataPopulator
 */
class GeneratedNestedSetTest extends CmsTestBase {

	/**
	 * A convenience method to dump the page rows.
	 */
	private function showPageItems()
	{
		$tree = PagePeer::retrieveTree();
		$iterator = new RecursiveIteratorIterator($tree, RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $item) { /* @var        $item Page */
			echo str_repeat('- ', $iterator->getDepth())
			, $item->getId() , ': '
			, $item->getTitle()
			, ' [', $item->getLeftValue(), ':', $item->getRightValue() , ']'
			. "\n";
		}
	}

	/**
	 * Adds a new Page row with specified parent Id.
	 *
	 * @param      int $parentId
	 */
	protected function addNewChildPage($parentId)
	{
		$db = Propel::getConnection(PagePeer::DATABASE_NAME);

		//$db->beginTransaction();

		$parent = PagePeer::retrieveByPK($parentId);
		$page = new Page();
		$page->setTitle('new page '.time());
		$page->insertAsLastChildOf($parent);
		$page->save();

		//$db->commit();
	}

	/**
	 * Asserts that the Page table tree integrity is intact.
	 */
	protected function assertPageTreeIntegrity()
	{
		$db = Propel::getConnection(PagePeer::DATABASE_NAME);

		$values = array();
		$log = '';

		foreach ($db->query('SELECT Id, LeftChild, RightChild, Title FROM Page', PDO::FETCH_NUM) as $row) {

			list($id, $leftChild, $rightChild, $title) = $row;

			if (!in_array($leftChild, $values)) {
				$values[] = (int) $leftChild;
			} else {
				$this->fail('Duplicate LeftChild value '.$leftChild);
			}

			if (!in_array($rightChild, $values)) {
				$values[] = (int) $rightChild;
			} else {
				$this->fail('Duplicate RightChild value '.$rightChild);
			}

			$log .= "[$id($leftChild:$rightChild)]";
		}

		sort($values);

		if ($values[count($values)-1] != count($values)) {
			$message = sprintf("Tree integrity NOT ok (%s)\n", $log);
			$message .= sprintf('Integrity error: value count: %d, high value: %d', count($values), $values[count($values)-1]);
			$this->fail($message);
		}

	}

	/**
	 * Tests adding a node to the Page tree.
	 */
	public function testAdd()
	{
		$db = Propel::getConnection(PagePeer::DATABASE_NAME);

		// I'm not sure if the specific ID matters, but this should match original
		// code.  The ID will change with subsequent runs (e.g. the first time it will be 11)
		$startId = $db->query('SELECT MIN(Id) FROM Page')->fetchColumn();
		$this->addNewChildPage($startId + 10);
		$this->assertPageTreeIntegrity();
	}

}
