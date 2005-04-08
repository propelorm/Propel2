<?php
/*
 *  $Id: BookstoreDataPopulator.php,v 1.3 2004/11/28 22:27:32 hlellelid Exp $
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
 
require_once 'bookstore/Book.php';
require_once 'bookstore/Author.php';
require_once 'bookstore/Media.php';
require_once 'bookstore/Publisher.php';
require_once 'bookstore/Review.php';
require_once 'bookstore/BookClubList.php';
require_once 'bookstore/BookListRel.php';

/**
 * Populates data needed by the bookstore unit tests.
 * 
 * This classes uses the actual Propel objects to do the population rather than
 * inserting directly into the database.  This will have a performance hit, but will
 * benefit from increased flexibility (as does anything using Propel).
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class BookstoreDataPopulator {

	public static function populate() {

		// Add publisher records
		// ---------------------
   
		//print "\nAdding some new publishers to the list\n";
		//print "--------------------------------------\n\n";
		
		$scholastic = new Publisher();
		$scholastic->setName("Scholastic");
		// do not save, will do later to test cascade
		//print "Added publisher \"Scholastic\" [not saved yet].\n";
		
		$morrow = new Publisher();
		$morrow->setName("William Morrow");
		$morrow->save();    
		$morrow_id = $morrow->getId();
		//print "Added publisher \"William Morrow\" [id = $morrow_id].\n";
		
		$penguin = new Publisher();
		$penguin->setName("Penguin");
		$penguin->save();    
		$penguin_id = $penguin->getId();
		//print "Added publisher \"Penguin\" [id = $penguin_id].\n";
		
		$vintage = new Publisher();
		$vintage->setName("Vintage");
		$vintage->save();    
		$vintage_id = $vintage->getId();
		//print "Added publisher \"Vintage\" [id = $vintage_id].\n";
			
	 
		// Add author records
		// ------------------

		//print "\nAdding some new authors to the list\n";
		//print "--------------------------------------\n\n";
		
		$rowling = new Author();
		$rowling->setFirstName("J.K.");
		$rowling->setLastName("Rowling");    
		// no save()
		//print "Added author \"J.K. Rowling\" [not saved yet].\n";
		
		$stephenson = new Author();
		$stephenson->setFirstName("Neal");
		$stephenson->setLastName("Stephenson");    
		$stephenson->save();
		$stephenson_id = $stephenson->getId();
		//print "Added author \"Neal Stephenson\" [id = $stephenson_id].\n";
		
		$byron = new Author();
		$byron->setFirstName("George");
		$byron->setLastName("Byron");    
		$byron->save();
		$byron_id = $byron->getId();
		//print "Added author \"George Byron\" [id = $byron_id].\n";
		
		
		$grass = new Author();
		$grass->setFirstName("Gunter");
		$grass->setLastName("Grass");    
		$grass->save();
		$grass_id = $grass->getId();
		//print "Added author \"Gunter Grass\" [id = $grass_id].\n";
	 

		// Add book records
		// ----------------
	 		
		//print "\nAdding some new books to the list\n";
		//print "-------------------------------------\n\n";
		
		$phoenix = new Book();
		$phoenix->setTitle("Harry Potter and the Order of the Phoenix");
		$phoenix->setISBN("043935806X");
		$phoenix->setAuthor($rowling);
		$phoenix->setPublisher($scholastic);    
		$phoenix->save();
		$phoenix_id = $phoenix->getId();
		// print "Added book \"Harry Potter and the Order of the Phoenix\" [id = $phoenix_id].\n";
		
		$qs = new Book();
		$qs->setISBN("0380977427");
		$qs->setTitle("Quicksilver");
		$qs->setAuthor($stephenson);
		$qs->setPublisher($morrow);
		$qs->save();
		$qs_id = $qs->getId();
		// print "Added book \"Quicksilver\" [id = $qs_id].\n";

		$dj = new Book();
		$dj->setISBN("0140422161");
		$dj->setTitle("Don Juan");
		$dj->setAuthor($byron);
		$dj->setPublisher($penguin);
		$dj->save();
		$dj_id = $dj->getId();
		// print "Added book \"Don Juan\" [id = $dj_id].\n";
		
		$td = new Book();
		$td->setISBN("067972575X");
		$td->setTitle("The Tin Drum");
		$td->setAuthor($grass);
		$td->setPublisher($vintage);
		$td->save();
		$td_id = $td->getId();
		// print "Added book \"The Tin Drum\" [id = $td_id].\n";
		
		// Add review records
		// ------------------
		
		//print "\nAdding some book reviews to the list\n";
		//print "------------------------------------\n\n";
		
		$r1 = new Review();
		$r1->setBook($phoenix);
		$r1->setReviewedBy("Washington Post");
		$r1->setRecommended(true);
		$r1->setReviewDate(time());
		$r1->save();
		$r1_id = $r1->getId();
		//print "Added Washington Post book review  [id = $r1_id].\n";
		
		$r2 = new Review();
		$r2->setBook($phoenix);
		$r2->setReviewedBy("New York Times");
		$r2->setRecommended(false);
		$r2->setReviewDate(time());
		$r2->save();
		$r2_id = $r2->getId();
		//print "Added New York Times book review  [id = $r2_id].\n";	
	
		$blob_path = PROPEL_TEST_BASE . '/etc/lob/tin_drum.gif';
		$clob_path =  PROPEL_TEST_BASE . '/etc/lob/tin_drum.txt';
		
		$m1 = new Media();
		$m1->setBook($td);
		$m1->setCoverImage(file_get_contents($blob_path));
		$m1->setExcerpt(file_get_contents($clob_path));        
		$m1->save();
		
		
		// Add book list records
		// ---------------------
		// (this is for many-to-many tests)
		
		$blc1 = new BookClubList();
		$blc1->setGroupLeader("Crazyleggs");
		$blc1->setTheme("Happiness");
		
		$brel1 = new BookListRel();
		$brel1->setBook($phoenix);
		
		$brel2 = new BookListRel();
		$brel2->setBook($dj);

		$blc1->addBookListRel($brel1);
		$blc1->addBookListRel($brel2);
		
		
	}
	
	public static function depopulate() {
		
		AuthorPeer::doDeleteAll();
		BookPeer::doDeleteAll();
		PublisherPeer::doDeleteAll();
		ReviewPeer::doDeleteAll();
		MediaPeer::doDeleteAll();
		BookClubListPeer::doDeleteAll();
		
	}

}