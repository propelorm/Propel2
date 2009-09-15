<?php
require_once 'bookstore/BookstoreTestBase.php';

/* It's only fair to admit that these tests were carefully crafted
after studying the current implementation to make it look as bad as
possible. I am really sorry. :-( */

class Ticket520Test extends BookstoreTestBase {

	public function testNewObjectsAvailableWhenSaveNotCalled() {
		$a = new Author();
		$a->setFirstName("Douglas");
		$a->setLastName("Adams");

		$b1 = new Book();
		$b1->setTitle("The Hitchhikers Guide To The Galaxy");
		$a->addBook($b1);

		$b2 = new Book();
		$b2->setTitle("The Restaurant At The End Of The Universe");
		$a->addBook($b2);

		/* As of revision 851, this passes as new objects (here: the Author)
		always contain all added FK-related objects (here: the Books) in
		their internal $colBooks collection. */
		$books = $a->getBooks();
		$this->assertContains($b1, $books);
		$this->assertContains($b2, $books);
	}

	public function testNewObjectsWithCriteria() {

		$a = new Author();
		$a->setFirstName("Douglas");
		$a->setLastName("Adams");

		$b1 = new Book();
		$b1->setTitle("The Hitchhikers Guide To The Galaxy");
		$a->addBook($b1);

		$b2 = new Book();
		$b2->setTitle("The Restaurant At The End Of The Universe");
		$a->addBook($b2);

		$c = new Criteria();
		$c->add(BookPeer::TITLE, "%Hitchhiker%", Criteria::LIKE);

		/* As of revision 851, this fails because new objects like the Author
		always contain added objects in their internal collection but are unable
		to apply any Criteria. */

		$guides = $a->getBooks($c);
		$this->assertEquals(1, count($guides));
		foreach ($guides as $book) {
			$this->assertEquals($b1, $book);
		}
	}

	public function testSavedObjectsWithCriteria() {
		$a = new Author();
		$a->setFirstName("Douglas");
		$a->setLastName("Adams");

		$b1 = new Book();
		$b1->setTitle("The Hitchhikers Guide To The Galaxy");
		$a->addBook($b1);

		$b2 = new Book();
		$b2->setTitle("The Restaurant At The End Of The Universe");
		$a->addBook($b2);

		$c = new Criteria();
		$c->add(BookPeer::TITLE, "%Hitchhiker%", Criteria::LIKE);

		/* This is the same as testNewObjectsWithCriteria EXCEPT we're now going
		to save(). Now the $author and related objects are no longer new
		and thus the criteria will be applied in the database.

		Apart from that the fix is for sure not trivial, observable behaviour
		of the $author should not depend on having called save() or not... */

		$booksBeforeSave = $a->getBooks($c);
		$a->save();
		$booksAfterSave = $a->getBooks($c);

		// As of revision 851, this passes...
		$this->assertEquals(1, count($booksAfterSave));
		foreach ($booksAfterSave as $book) {
			$this->assertEquals($b1, $book);
		}

		/* ... but this would fail. Commented out because it's covered
		by testNewObjectsWithCriteria(). */
		//$this->assertEquals($booksBeforeSave, $booksAfterSave);
	}

	public function testAddNewObjectAfterSave() {
		/* This is like testNewObjectsAvailableWhenSaveNotCalled(),
		but this time we save the author before adding the book. */

		$a = new Author();
		$a->setFirstName("Douglas");
		$a->setLastName("Adams");

		$a->save();

		$b1 = new Book();
		$b1->setTitle("The Hitchhikers Guide To The Galaxy");
		$a->addBook($b1);

		/* As of revision 851, although testNewObjectsAvailableWhenSaveNotCalled()
		worked, this will fail. Because the author has been saved this time,
		it will only check the database and not see the new (unsaved) book. */
		$books = $a->getBooks();
		$this->assertEquals(1, count($books));
		$this->assertContains($b1, $books);

		/* Now this is the initial ticket 520: If we have a saved author,
		add a new book but happen to call getBooks() before we call save() again,
		the book is lost. As of revision 851, this will fail: */
		$a->save();
		$this->assertFalse($b1->isNew());

	}

	public function testAddNewObjectAfterSaveWithPoisonedCache() {
		/* This is like testAddNewObjectAfterSave(),
		but this time we "poison" the author's $colBooks cache
		before adding the book by calling getBooks(). */

		$a = new Author();
		$a->setFirstName("Douglas");
		$a->setLastName("Adams");

		$a->save();
		$a->getBooks();

		$b1 = new Book();
		$b1->setTitle("The Hitchhikers Guide To The Galaxy");
		$a->addBook($b1);

		/* As of revision 851, this passes. This is because the following
		call will not look at the database because the same (nil) criteria
		is used as in the call to getBooks() above. The book has been added to
		the cache inside the Author object that now is returned. */
		$books = $a->getBooks();
		$this->assertEquals(1, count($books));
		$this->assertContains($b1, $books);
	}

	public function testCachePoisoning() {
		/* Like testAddNewObjectAfterSaveWithPoisonedCache, emphasizing
		cache poisoning. */

		$a = new Author();
		$a->setFirstName("Douglas");
		$a->setLastName("Adams");

		$a->save();

		$c = new Criteria();
		$c->add(BookPeer::TITLE, "%Restaurant%", Criteria::LIKE);

		$this->assertEquals(0, count($a->getBooks($c)));

		$b1 = new Book();
		$b1->setTitle("The Hitchhikers Guide To The Galaxy");
		$a->addBook($b1);

		/* Like testAddNewObjectAfterSaveWithPoisonedCache, but this time
		with a real criteria. As of revision 851, this fails because
		the $b1 is returned although it should not (does not match the
		criteria). This is the first comment on the 520 ticket. */
		$this->assertEquals(0, count($a->getBooks($c)));

		/* If we called $a->getBooks() now, $b1 would be lost although
		it should be in the result. Already covered by
		testAddNewObjectAfterSave().

		Instead, this time we call save() again, saving the book. However,
		although everything is in the DB, the cache is still wrong like
		in the assertion before. */
		$a->save();
		$this->assertFalse($b1->isNew());
		$this->assertEquals(0, count($a->getBooks($c)));
	}

	public function testDeletedBookDisappears() {
		$a = new Author();
		$a->setFirstName("Douglas");
		$a->setLastName("Adams");

		$b1 = new Book();
		$b1->setTitle("The Hitchhikers Guide To The Galaxy");
		$a->addBook($b1);

		$b2 = new Book();
		$b2->setTitle("The Restaurant At The End Of The Universe");
		$a->addBook($b2);

		/* As you cannot write $a->remove($b2), you have to delete $b2
		directly. */

		/* All objects unsaved. As of revision 851, this circumvents the
		$colBooks cache. Anyway, fails because getBooks() never checks if
		a colBooks entry has been deleted. */
		$this->assertEquals(2, count($a->getBooks()));
		$b2->delete();
		$this->assertEquals(1, count($a->getBooks()));

		/* Even if we had saved everything before and the delete() had
		actually updated the DB, the $b2 would still be a "zombie" in
		$a's $colBooks field. */
	}

	public function testNewObjectsGetLostOnJoin() {
		/* While testNewObjectsAvailableWhenSaveNotCalled passed as of
		revision 851, in this case we call getBooksJoinPublisher() instead
		of just getBooks(). get...Join...() does not contain the check whether
		the current object is new, it will always consult the DB and lose the
		new objects entirely. Thus the test fails. (At least for Propel 1.2 ?!?) */

		$a = new Author();
		$a->setFirstName("Douglas");
		$a->setLastName("Adams");

		$p = new Publisher();
		$p->setName('Pan Books Ltd.');

		$b1 = new Book();
		$b1->setTitle("The Hitchhikers Guide To The Galaxy");
		$b1->setPublisher($p); // uh... did not check that :^)
		$a->addBook($b1);

		$b2 = new Book();
		$b2->setTitle("The Restaurant At The End Of The Universe");
		$b2->setPublisher($p);
		$a->addBook($b2);

		$books = $a->getBooksJoinPublisher();
		$this->assertEquals(2, count($books));
		$this->assertContains($b1, $books);
		$this->assertContains($b2, $books);

		$a->save();
		$this->assertFalse($b1->isNew());
		$this->assertFalse($b2->isNew());
	}

}
