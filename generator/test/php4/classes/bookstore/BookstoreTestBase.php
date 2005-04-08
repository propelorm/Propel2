<?php
/*
 *  $Id: BookstoreTestBase.php,v 1.2 2004/11/21 16:44:05 micha Exp $
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
 
require_once 'PHPUnit/TestCase.php';
require_once 'bookstore/BookstoreDataPopulator.php';

/**
 * Base class contains some methods shared by subclass test cases.
 */
class BookstoreTestBase extends PHPUnit_TestCase
{
  
  /**
  * This is run before each unit test; it populates the database.
  */
  function setUp()
  {
    parent::setUp();
    $e = BookstoreDataPopulator::populate();

    if (Propel::isError($e)) {
      die("Unable to populate bookstore: " . $e->getMessage() . "\n");
    }
  }
  
  /**
  * This is run after each unit test.  It empties the database.
  */
  function tearDown()
  {
    $e = BookstoreDataPopulator::depopulate();
    if (Propel::isError($e)) die("Unable to depopulate bookstore: " . $e->getMessage() . "\n");
    $this->assertEquals(0, count(BookPeer::doSelect(new Criteria())), "Expect book table to be empty.");
    $this->assertEquals(0, count(AuthorPeer::doSelect(new Criteria())), "Expect author table to be empty.");
    $this->assertEquals(0, count(PublisherPeer::doSelect(new Criteria())), "Expect publisher table to be empty.");
    $this->assertEquals(0, count(ReviewPeer::doSelect(new Criteria())), "Expect review table to be empty.");
    $this->assertEquals(0, count(MediaPeer::doSelect(new Criteria())), "Expect media table to be empty.");
    parent::tearDown();
  }
  
}