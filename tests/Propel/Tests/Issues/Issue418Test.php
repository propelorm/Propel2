<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookClubList;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;


class Issue418Test extends BookstoreEmptyTestBase
{
    /**
     * @expectedException \PHPUnit_Framework_Error_Notice
     */
    public function testArrayToStringConversion()
    {
        $author  = new Author();
        $author->setFirstName("Jim");
        $author->setLastName("Jefferson");
        $author->save();

        $pub = new Publisher();
        $pub->setName("New Media");
        $pub->save();

        $quick = new Book();
        $quick->setISBN("0380977427");
        $quick->setTitle("Quicksilver");
        $quick->setAuthor($author);
        $quick->setPublisher($pub);
        $quick->save();

        $burt = new Book();
        $burt->setISBN("2313131");
        $burt->setTitle("Burton");
        $burt->setAuthor($author);
        $burt->setPublisher($pub);
        $burt->save();

        $list = new BookClubList();
        $list->setTheme('Black');
        $list->setGroupLeader('Mr. Simon');

        $collBooks = new ObjectCollection();
        $prevBooks = array('Quicksilver','Burton');
        foreach($prevBooks as $title){
            $collBooks->append(BookQuery::create()->filterByTitle($title)->findOneOrCreate());
        }

        $list->setBooks($collBooks);
        $list->save();

        $collection = BookQuery::create()->findByTitle($prevBooks);

        foreach($collection as $book){
            $postBooks[] = $book->getTitle();
        }

        $this->assertContains('Quicksilver',$postBooks);
        $this->assertContains('Burton',$postBooks);
    }

}
