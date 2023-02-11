<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    /**
     * @return void
     */
    public function testUniqueValidatorPass()
    {
        $publisher = new Publisher();
        $publisher->setName('Happy Reading');
        $publisher->setWebsite('http://www.happyreading.com');
        $publisher->save();
        $isValid = $publisher->validate();
        $this->assertTrue($isValid);
    }

    /**
     * @return void
     */
    public function testUniqueValidatorIgnoresItself()
    {
        $publisher = new Publisher();
        $publisher->setName('Happy Reading');
        $publisher->save();

        $publisher->setName('Happy Reading');

        $this->assertTrue($publisher->validate());

        $publisher->delete();
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testUniqueValidatorPassIfNull()
    {
        $book = new Book();
        $book->setTitle('The return of Sherlock Holmes');
        $this->assertTrue($book->validate());
    }

    /**
     * @return void
     */
    public function testUniqueValidatorAlwaysPassIfNull()
    {
        $book = new Book();
        $book->setTitle('The return of Sherlock Holmes');
        $book->save();

        $book1 = new Book();
        $book1->setTitle('Dracula');

        $this->assertTrue($book1->validate());

        $book->delete();
    }
}
