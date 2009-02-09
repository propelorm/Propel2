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
class GeneratedNestedSetObjectTest extends CmsTestBase {

	/**
	 * Test xxxNestedSet::isRoot() as true
	 */
	public function testObjectIsRootTrue()
	{
		$pp = PagePeer::retrieveRoot(1);
		$this->assertTrue($pp->isRoot(), 'Node must be root');
	}

	/**
	 * Test xxxNestedSet::isRoot() as false
	 */
	public function testObjectIsRootFalse()
	{
		$c = new Criteria(PagePeer::DATABASE_NAME);
		$c->add(PagePeer::TITLE, 'school', Criteria::EQUAL);

		$school = PagePeer::doSelectOne($c);
		$this->assertFalse($school->isRoot(), 'Node must not be root');
	}

	/**
	 * Test xxxNestedSet::retrieveParent() as true.
	 */
	public function testObjectRetrieveParentTrue()
	{
		$c = new Criteria(PagePeer::DATABASE_NAME);
		$c->add(PagePeer::TITLE, 'school', Criteria::EQUAL);

		$school = PagePeer::doSelectOne($c);
		$this->assertNotNull($school->retrieveParent(), 'Parent node must exist');
	}

	/**
	 * Test xxxNestedSet::retrieveParent() as false.
	 */
	public function testObjectRetrieveParentFalse()
	{
		$c = new Criteria(PagePeer::DATABASE_NAME);
		$c->add(PagePeer::TITLE, 'home', Criteria::EQUAL);

		$home = PagePeer::doSelectOne($c);
		$this->assertNull($home->retrieveParent(), 'Parent node must not exist and retrieved not be null');
	}

	/**
	 * Test xxxNestedSet::hasParent() as true.
	 */
	public function testObjectHasParentTrue()
	{
		$c = new Criteria();
		$c->add(PagePeer::TITLE, 'school', Criteria::EQUAL);

		$school = PagePeer::doSelectOne($c);
		$this->assertTrue($school->hasParent(), 'Node must have parent node');
	}

	/**
	 * Test xxxNestedSet::hasParent() as false
	 */
	public function testObjectHasParentFalse()
	{
		$c = new Criteria();
		$c->add(PagePeer::TITLE, 'home', Criteria::EQUAL);

		$home = PagePeer::doSelectOne($c);
		$this->assertFalse($home->hasParent(), 'Root node must not have parent');
	}

	/**
	 * Test xxxNestedSet::isLeaf() as true.
	 */
	public function testObjectIsLeafTrue()
	{
		$c = new Criteria();
		$c->add(PagePeer::TITLE, 'simulator', Criteria::EQUAL);

		$simulator = PagePeer::doSelectOne($c);
		$this->assertTrue($simulator->isLeaf($simulator), 'Node must be a leaf');
	}

	/**
	 * Test xxxNestedSet::isLeaf() as false
	 */
	public function testObjectIsLeafFalse()
	{
		$c = new Criteria();
		$c->add(PagePeer::TITLE, 'contact', Criteria::EQUAL);

		$contact = PagePeer::doSelectOne($c);
		$this->assertFalse($contact->isLeaf($contact), 'Node must not be a leaf');
	}

	/**
	 * Test xxxNestedSet::makeRoot()
	 */
	public function testObjectMakeRoot()
	{
		$page = new Page();
		$page->makeRoot();
		$this->assertEquals(1, $page->getLeftValue(), 'Node left value must equal 1');
		$this->assertEquals(2, $page->getRightValue(), 'Node right value must equal 2');
	}

	/**
	 * Test xxxNestedSet::makeRoot() exception
	 * @expectedException PropelException
	 */
	public function testObjectMakeRootException()
	{
		$c = new Criteria();
		$c->add(PagePeer::TITLE, 'home', Criteria::EQUAL);

		$home = PagePeer::doSelectOne($c);
		$home->makeRoot();
	}

	/**
	 * Test xxxNestedSet::getDescendants()
	 */
	public function testPeerGetDescendants()
	{
		$nodesWithoutPool = array();
		CategoryPeer::clearInstancePool();
		$cat = CategoryPeer::retrieveRoot(1);
		$children = $cat->getDescendants();
		foreach($children as $child)
		{
			$nodesWithoutPool[] = $child->getTitle();
		}
		$this->assertEquals($nodesWithoutPool, array('Cat_1_1', 'Cat_1_1_1', 'Cat_1_1_1_1'));
	}

	/**
	 * Test xxxNestedSet::getDescendantsTwice()
	 */
	public function testPeerGetDescendantsTwice()
	{
		$nodesWithoutPool = array();
		$nodesWithPool = array();

		CategoryPeer::clearInstancePool();
		$cat = CategoryPeer::retrieveRoot(1);
		$children = $cat->getDescendants();
		foreach($children as $child)
		{
			$nodesWithoutPool[] = $child->getTitle();
		}

		$cat = CategoryPeer::retrieveRoot(1);
		$children = $cat->getDescendants();
		foreach($children as $child)
		{
			$nodesWithPool[] = $child->getTitle();
		}
		$this->assertEquals($nodesWithoutPool, $nodesWithPool, 'Retrieved nodes must be the same with and without InstancePooling');
	}
}
