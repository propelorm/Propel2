<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder;

use Foo\Bar\NamespacedAuthor;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Tests for Namespaces in generated classes class
 * Requires a build of the 'namespaced' fixture
 *
 * @group database
 */
class NamespaceTest extends TestCaseFixturesDatabase
{
    public function testInsert()
    {
        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('foo');
        $book->setISBN('something');
        $book->save();
        $this->assertFalse($book->isNew());

        $publisher = new \Baz\NamespacedPublisher();
        $publisher->setName('pub');
        $publisher->save();
        $this->assertFalse($publisher->isNew());
    }

    public function testUpdate()
    {
        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('foo');
        $book->setISBN('something');
        $book->save();
        $book->setTitle('bar');
        $book->save();
        $this->assertFalse($book->isNew());
    }

    public function testRelate()
    {
        $author = new \Foo\Bar\NamespacedAuthor();
        $author->setFirstName('Chuck');
        $author->setLastname('Norris');
        $book = new \Foo\Bar\NamespacedBook();
        $book->setNamespacedAuthor($author);
        $book->setTitle('foo');
        $book->setISBN('something');
        $book->save();
        $this->assertFalse($book->isNew());
        $this->assertFalse($author->isNew());

        $author = new \Foo\Bar\NamespacedAuthor();
        $author->setFirstName('Henning');
        $author->setLastname('Mankell');
        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('Mördare utan ansikte');
        $book->setISBN('1234');
        $author->addNamespacedBook($book);
        $author->save();
        $this->assertFalse($book->isNew());
        $this->assertFalse($author->isNew());

        $publisher = new \Baz\NamespacedPublisher();
        $publisher->setName('pub');
        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('Där vi en gång gått');
        $book->setISBN('1234');
        $book->setNamespacedPublisher($publisher);
        $book->save();
        $this->assertFalse($book->isNew());
        $this->assertFalse($publisher->isNew());
    }

    public function testBasicQuery()
    {
        \Foo\Bar\NamespacedBookQuery::create()->deleteAll();
        \Baz\NamespacedPublisherQuery::create()->deleteAll();
        $noNamespacedBook = \Foo\Bar\NamespacedBookQuery::create()->findOne();
        $this->assertNull($noNamespacedBook);
        $noPublisher = \Baz\NamespacedPublisherQuery::create()->findOne();
        $this->assertNull($noPublisher);
    }

    public function testFind()
    {
        \Foo\Bar\NamespacedBookQuery::create()->deleteAll();
        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('War And Peace');
        $book->setISBN('1234');
        $book->save();
        $book2 = \Foo\Bar\NamespacedBookQuery::create()->findPk($book->getId());
        $this->assertEquals($book, $book2);
        $book3 = \Foo\Bar\NamespacedBookQuery::create()->findOneByTitle($book->getTitle());
        $this->assertEquals($book, $book3);
    }

    public function testGetRelatedManyToOne()
    {
        \Foo\Bar\NamespacedBookQuery::create()->deleteAll();
        \Baz\NamespacedPublisherQuery::create()->deleteAll();
        $publisher = new \Baz\NamespacedPublisher();
        $publisher->setName('pub');
        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('Something');
        $book->setISBN('1234');
        $book->setNamespacedPublisher($publisher);
        $book->save();
        $book2 = \Foo\Bar\NamespacedBookQuery::create()->findPk($book->getId());
        $publisher2 = $book2->getNamespacedPublisher();
        $this->assertEquals($publisher->getId(), $publisher2->getId());
    }

    public function testGetRelatedOneToMany()
    {
        \Foo\Bar\NamespacedBookQuery::create()->deleteAll();
        \Baz\NamespacedPublisherQuery::create()->deleteAll();
        $author = new \Foo\Bar\NamespacedAuthor();
        $author->setFirstName('Foo');
        $author->setLastName('Bar');

        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('Quux');
        $book->setISBN('1235');
        $book->setNamespacedAuthor($author);
        $book->save();

        /** @var NamespacedAuthor $author2 */
        $author2 = \Foo\Bar\NamespacedAuthorQuery::create()->findPk($author->getId());
        $book2 = $author2->getNamespacedBooks()[0];
        $this->assertEquals($book->getId(), $book2->getId());
    }

    public function testFindWithManyToOne()
    {
        \Foo\Bar\NamespacedBookQuery::create()->deleteAll();
        \Baz\NamespacedPublisherQuery::create()->deleteAll();
        $publisher = new \Baz\NamespacedPublisher();
        $publisher->setName('TestName');
        $publisher->save();

        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('asdf');
        $book->setISBN('something');
        $book->setNamespacedPublisher($publisher);
        $book->save();
        $book2 = \Foo\Bar\NamespacedBookQuery::create()
            ->joinWith('namespacedPublisher')
            ->findPk($book->getId());
        $publisher2 = $book2->getNamespacedPublisher();
        $this->assertEquals($publisher->getId(), $publisher2->getId());
    }

    public function testFindWithOneToMany()
    {
        \Foo\Bar\NamespacedBookQuery::create()->deleteAll();
        \Foo\Bar\NamespacedAuthorQuery::create()->deleteAll();
        $author = new \Foo\Bar\NamespacedAuthor();
        $author->setFirstName('Foo');
        $author->setLastName('Bar');
        $book = new \Foo\Bar\NamespacedBook();
        $book->setTitle('asdf');
        $book->setISBN('something');
        $book->setNamespacedAuthor($author);
        $book->save();
        $author2 = \Foo\Bar\NamespacedAuthorQuery::create()
            ->joinWith('namespacedBook')
            ->findPk($author->getId());
        $book2 = $author2->getNamespacedBooks()[0];
        $this->assertEquals($book->getId(), $book2->getId());
    }

    public function testManyToMany()
    {
        \Foo\Bar\NamespacedBookQuery::create()->deleteAll();
        \Baz\NamespacedBookClubQuery::create()->deleteAll();
        \Baz\NamespacedBookListRelQuery::create()->deleteAll();

        $book1 = new \Foo\Bar\NamespacedBook();
        $book1->setTitle('bar');
        $book1->setISBN('1234');
        $book1->save();
        $book2 = new \Foo\Bar\NamespacedBook();
        $book2->setTitle('foo');
        $book2->setISBN('4567');
        $book2->save();


        $bookClub1 = new \Baz\NamespacedBookClub();
        $bookClub1->addNamespacedBook($book1);
        $bookClub1->addNamespacedBook($book2);
        $bookClub1->setGroupLeader('Someone1');
        $bookClub1->save();
        $nbRels = \Baz\NamespacedBookListRelQuery::create()->count();
        $this->assertEquals(2, $nbRels);

        $bookClub2 = new \Baz\NamespacedBookClub();
        $bookClub2->addNamespacedBook($book1);
        $bookClub2->setGroupLeader('Someone2');
        $bookClub2->save();

        $this->assertEquals(2, $book1->countNamespacedBookClubs());
        $this->assertEquals(1, $book2->countNamespacedBookClubs());

        $nbRels = \Baz\NamespacedBookListRelQuery::create()->count();
        $this->assertEquals(3, $nbRels);
        $books = \Foo\Bar\NamespacedBookQuery::create()
            ->joinWith('namespacedBookListRel')
            ->joinWith('namespacedBookListRel.namespacedBookClub')
            ->orderByTitle()
            ->find();
        $this->assertEquals(2, count($books));
    }

    public function testUseQuery()
    {
        $book = \Foo\Bar\NamespacedBookQuery::create()
            ->useNamespacedPublisherQuery()
                ->filterByName('foo')
            ->endUse()
            ->findOne();
        $this->assertNull($book);
    }
}
