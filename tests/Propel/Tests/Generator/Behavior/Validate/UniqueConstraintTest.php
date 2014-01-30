<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Validate;

use Propel\Tests\Bookstore\Behavior\ValidateBook as Book;
use Propel\Tests\Bookstore\Behavior\ValidatePublisher as Publisher;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests for Unique Constraint
 *
 * @author Cristiano Cinotti
 *
 * @group database
 */
class UniqueConstraintTest extends BookstoreTestBase
{
    public function testUniqueValidatorPass()
    {
        $publisher = new Publisher();
        $publisher->setName('Happy Reading');
        $publisher->setWebsite('http://www.happyreading.com');
        $this->assertTrue($publisher->validate());
    }

    public function testUniqueValidatorFail()
    {
        $publisher = new Publisher();
        $publisher->setName('Happy Reading');
        $publisher->setWebsite('http://www.happyreading.com');
        $publisher->save();

        $publisher1 = new Publisher();
        $publisher1->setName('Happy Reading');

        $this->assertFalse($publisher1->validate());

        $failures = $publisher1->getValidationFailures();
        $this->assertCount(1, $failures);
        $this->assertEquals('name', $failures[0]->getPropertyPath());
        $this->assertEquals('This value is already stored in your database', $failures[0]->getMessage());

        $publisher->delete();
    }

    public function testUniqueValidatorPassIfNull()
    {
        $book = new Book();
        $book->setTitle("The return of Sherlock Holmes");
        $this->assertTrue($book->validate());
    }

    public function testUniqueValidatorAlwaysPassIfNull()
    {
        $book = new Book();
        $book->setTitle("The return of Sherlock Holmes");
        $book->save();

        $book1 = new Book();
        $book1->setTitle('Dracula');

        $this->assertTrue($book1->validate());

        $book->delete();
    }

}
