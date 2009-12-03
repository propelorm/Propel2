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

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests the generated Peer behavior hooks.
 *
 * @author     Francois Zaninotto
 * @package    generator.behavior
 */
class PeerBehaviorTest extends BookstoreTestBase
{
  public function testStaticAttributes()
  {
    $this->assertEquals(Table3Peer::$customStaticAttribute, 1, 'staticAttributes hook is called when adding attributes');
    $this->assertEquals(Table3Peer::$staticAttributeBuilder, 'PHP5PeerBuilder', 'staticAttributes hook is called with the peer builder as parameter');
  }
  
  public function testStaticMethods()
  {
    $this->assertTrue(method_exists('Table3Peer', 'hello'), 'staticMethods hook is called when adding methods');
    $this->assertEquals(Table3Peer::hello(), 'PHP5PeerBuilder', 'staticMethods hook is called with the peer builder as parameter');
  }
  
  public function testPreSelect()
  {
    $con = Propel::getConnection(Table3Peer::DATABASE_NAME, Propel::CONNECTION_READ);
    $con->preSelect = 0;
    Table3Peer::doSelect(new Criteria, $con);
    $this->assertNotEquals($con->preSelect, 0, 'preSelect hook is called in doSelect()');
    $con->preSelect = 0;
    Table3Peer::doSelectOne(new Criteria, $con);
    $this->assertNotEquals($con->preSelect, 0, 'preSelect hook is called in doSelectOne()');
    $con->preSelect = 0;
    Table3Peer::doCount(new Criteria, $con);
    $this->assertNotEquals($con->preSelect, 0, 'preSelect hook is called in doCount()');
    $con->preSelect = 0;
    Table3Peer::doSelectStmt(new Criteria, $con);
    $this->assertNotEquals($con->preSelect, 0, 'preSelect hook is called in doSelectStmt()');
    // and for the doSelectJoin and doCountJoin methods, well just believe my word

    $con->preSelect = 0;
    Table3Peer::doSelect(new Criteria, $con);
    $this->assertEquals($con->preSelect, 'PHP5PeerBuilder', 'preSelect hook is called with the peer builder as parameter');
  }
  
  public function testPeerFilter()
  {
    Table3Peer::TABLE_NAME;
    $this->assertTrue(class_exists('testPeerFilter'), 'peerFilter hook allows complete manipulation of the generated script');
    $this->assertEquals(testPeerFilter::FOO, 'PHP5PeerBuilder', 'peerFilter hook is called with the peer builder as parameter');
  }
}
