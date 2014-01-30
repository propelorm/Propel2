<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Validate;

use Propel\Generator\Behavior\Validate\ValidateBehavior;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\Bookstore\Behavior\ValidateAuthor;
use Propel\Tests\Bookstore\Behavior\ValidateBook;
use Propel\Tests\Bookstore\Behavior\ValidatePublisher;
use Propel\Tests\Bookstore\Behavior\ValidateReader;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests for ValidateBehavior class
 *
 * @author Cristiano Cinotti
 *
 * @group database
 */
class ValidateBehaviorTest extends BookstoreTestBase
{
    /**
     * @private  array   The names of ValidateAuthor, ValidateBook, ValidatePublisher, ValidateReader classes.
     *                 This classes are created by test:prepare command
     */
    private $classes;

    public function assertPreConditions()
    {
        if (!class_exists('Propel\Tests\Bookstore\Behavior\ValidateAuthor')) {
            throw new \Exception('Please, run \'bin/propel test:prepare\' command before starting to test this behavior');
        }

        $this->classes[] = 'Propel\Tests\Bookstore\Behavior\ValidateAuthor';
        $this->classes[] = 'Propel\Tests\Bookstore\Behavior\ValidateBook';
        $this->classes[] = 'Propel\Tests\Bookstore\Behavior\ValidatePublisher';
        $this->classes[] = 'Propel\Tests\Bookstore\Behavior\ValidateReader';
        $this->classes[] = 'Propel\Tests\Bookstore\Behavior\ValidateReaderBook';
    }

    public function testHasValidateMethod()
    {
        foreach ($this->classes as $class) {
             $this->assertTrue(method_exists($class, 'validate'));
        }
    }

    public function testHasLoadValidatorMetadataMethod()
    {
        foreach ($this->classes as $class) {
             $this->assertTrue(method_exists($class, 'loadValidatorMetadata'));
        }
    }

    public function testHasAlreadyInValidationAttribute()
    {
        foreach ($this->classes as $class) {
             $this->assertClassHasAttribute('alreadyInValidation', $class);
        }
    }

    public function testHasValidationFailuresAttribute()
    {
        foreach ($this->classes as $class) {
             $this->assertClassHasAttribute('validationFailures', $class);
        }
    }

    public function testLoadValidatorMetadataMethodIsStatic()
    {
        foreach ($this->classes as $class) {
             $method = new \ReflectionMethod($class, 'loadValidatorMetadata');
             $this->assertTrue($method->isStatic());
        }
    }

    /**
     * @expectedException  Propel\Generator\Exception\InvalidArgumentException
     * @expectedExceptionMessage  Please, define your rules for validation.
     */
    public function testParametersNotDefined()
    {
        $schema = <<<EOF
<database name="bookstore-behavior">
  <table name="validate_author" description="Author Table">
    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
    <column name="first_name" required="true" type="VARCHAR" size="128" />
    <behavior name="validate" />
  </table>
</database>
EOF;
        QuickBuilder::buildSchema($schema);
    }

    /**
     * @expectedException  Propel\Generator\Exception\InvalidArgumentException
     * @expectedExceptionMessage  Please, define the column to validate.
     */
    public function testColumnNameNotDefined()
    {
        $schema = <<<EOF
<database name="bookstore-behavior">
  <table name="validate_author" description="Author Table">
     <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Author Id" />
     <column name="first_name" required="true" type="VARCHAR" size="128" description="First Name" />
     <behavior name="validate">
       <parameter name="rule1" value="{validator: NotNull}" />
       <parameter name="rule2" value="{column: first_name, validator: Length, options: {max: 128}}" />
      </behavior>
  </table>
  </database>
EOF;
        QuickBuilder::buildSchema($schema);
    }

    /**
     * @expectedException  Propel\Generator\Exception\InvalidArgumentException
     * @expectedExceptionMessage  Please, define the validator constraint.
     */
    public function testValidatorNameNotDefined()
    {
        $schema = <<<EOF
<database name="bookstore-behavior">
  <table name="validate_author" description="Author Table">
     <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Author Id" />
     <column name="first_name" required="true" type="VARCHAR" size="128" description="First Name" />
     <behavior name="validate">
       <parameter name="rule1" value="{column: first_name}" />
       <parameter name="rule2" value="{column: first_name, validator: Length, options: {max: 128}}" />
      </behavior>
  </table>
  </database>
EOF;
        QuickBuilder::buildSchema($schema);
    }

    /**
     * @expectedException  Propel\Generator\Exception\ConstraintNotFoundException
     * @expectedExceptionMessage  The constraint class MaximumLength does not exist.
     */
    public function testConstraintNameNotValid()
    {
        $schema = <<<EOF
<database name="bookstore-behavior">
  <table name="validate_author" description="Author Table">
     <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Author Id" />
     <column name="first_name" required="true" type="VARCHAR" size="128" description="First Name" />
     <behavior name="validate">
       <parameter name="rule1" value="{column: first_name, validator: NotNull}" />
       <parameter name="rule2" value="{column: first_name, validator: MaximumLength, options: {limit: 128}}" />
      </behavior>
  </table>
  </database>
EOF;
        QuickBuilder::buildSchema($schema);
    }

    /**
     * @expectedException  Propel\Generator\Exception\InvalidArgumentException
     * @expectedExceptionMessage  The options value, in <parameter> tag must be an array
     */
    public function testConstraintOptionsNotValid()
    {
        $schema = <<<EOF
<database name="bookstore-behavior">
  <table name="validate_author" description="Author Table">
     <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Author Id" />
     <column name="first_name" required="true" type="VARCHAR" size="128" description="First Name" />
     <behavior name="validate">
       <parameter name="rule1" value="{column: first_name, validator: NotNull, foo: bar}" />
       <parameter name="rule2" value="{column: first_name, validator: Length, options: 128}" />
      </behavior>
  </table>
  </database>
EOF;
        QuickBuilder::buildSchema($schema);
    }

    public function testSimpleValidationSuccess()
    {
        $author = new ValidateAuthor();
        $author->setFirstName('Oscar');
        $author->setLastName('Wilde');
        $author->setEmail('oscar.wilde@gmail.com');
        $author->setBirthday('1854-10-16');

        $res = $author->validate();

        $this->assertTrue($res, 'Expected validation is successful');
    }

    public function testComplexValidationSuccess()
    {
        $author = new ValidateAuthor();
        $author->setId(1);
        $author->setFirstName('Oscar');
        $author->setLastName('Wilde');
        $author->setEmail('oscar.wilde@gmail.com');
        $author->setBirthday('1854-10-16');

        $publisher = new ValidatePublisher();
        $publisher->setName('Sancho Panza');
        $publisher->setWebsite('http://www.sancho-panza.com');

        $book = new ValidateBook();
        $book->setId(1);
        $book->setValidateAuthor($author);
        $book->setValidatePublisher($publisher);
        $book->setTitle('The Picture of Dorian Gray');

        $reader1 = new ValidateReader();
        $reader1->setId(1);
        $reader1->setFirstName('John');
        $reader1->setLastName('Smith');

        $reader2 = new ValidateReader();
        $reader2->setId(2);
        $reader2->setFirstName('Mark');
        $reader2->setLastName('Brown');

        $book->addValidateReader($reader1);
        $book->addValidateReader($reader2);

        $res = $book->validate();
        $this->assertTrue($res, 'Expected validation is successful');
    }

    public function testSingleValidationFailure()
    {
       $reader = new ValidateReader();
       $reader->setId(14);
       $reader->setFirstName('Felicity');
       $reader->setLastName('Stamm');
       $reader->setEmail('f.stamm@'); //failure
       $reader->setBirthday('1989-07-24');

       $res = $reader->validate();

       $this->assertFalse($res, 'This validation expected to fail');

       $failures = $reader->getValidationFailures();

       $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $failures);
       $this->assertEquals(1, count($failures), 'Only one constraint violation object');

       $failure = $failures[0];
       $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolation', $failure);
       $this->assertEquals('email', $failure->getPropertyPath(), 'email property expected to fail');
    }

    public function testMultipleValidationFailures()
    {
       $reader = new ValidateReader();
       $reader->setId(18);
       $reader->setFirstName('Bo'); //failure: less than 4 chars
       $reader->setLastName(null); //failure
       $reader->setEmail('zora.null@'); //failure
       $reader->setBirthday('1983-09-22');

       $failedProperties = array('last_name', 'first_name', 'email');

       $res = $reader->validate();

       $this->assertFalse($res, 'This validation expected to fail');

       $failures = $reader->getValidationFailures();

       $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $failures);
       $this->assertEquals(3, count($failures), 'Three constraint violation objects expected');

       foreach ($failures as $failure) {
           $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolation', $failure);
           $this->assertTrue(in_array($failure->getPropertyPath(), $failedProperties));
       }
    }

    public function testComplexValidationSingleFailure()
    {
        $author = new ValidateAuthor();
        $author->setId(1);
        $author->setFirstName('Christine');
        $author->setLastName('Wraz');
        $author->setEmail('christine@hermann.com');
        $author->setBirthday('1954-10-16');

        $publisher = new ValidatePublisher();
        $publisher->setId(5);
        $publisher->setName('Gorkzani Group');
        $publisher->setWebsite('http://www.gorkzany.com');

        $book = new ValidateBook();
        $book->setId(1);
        $book->setValidateAuthor($author);
        $book->setValidatePublisher($publisher);
        $book->setTitle(null); //failed

        $reader = new ValidateReader();
        $reader->setId(1);
        $reader->setFirstName('John');
        $reader->setLastName('Smith');
        $reader->setEmail('jsmith@hermann.com');
        $reader->setBirthday('1974-11-19');
        $book->addValidateReader($reader);

        $res = $book->validate();

        $this->assertFalse($res, 'This validation expected to fail');

        $failures = $book->getValidationFailures();

        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $failures);
        $this->assertEquals(1, count($failures), 'Only one constraint violation object');

        $failure = $failures[0];
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolation', $failure);
        $this->assertEquals('title', $failure->getPropertyPath(), 'title property expected to fail');
    }

    public function testComplexValidationRelatedObjectsSingleFailure()
    {
        $author = new ValidateAuthor();
        $author->setId(1);
        $author->setFirstName('Christine');
        $author->setLastName('Wraz');
        $author->setEmail('christine@hermann.com');
        $author->setBirthday('1954-10-16');

        $publisher = new ValidatePublisher();
        $publisher->setId(5);
        $publisher->setName('Gorkzani Group');
        $publisher->setWebsite('gorkzany.com'); //failed: not valid url

        $book = new ValidateBook();
        $book->setId(1);
        $book->setValidateAuthor($author);
        $book->setValidatePublisher($publisher);
        $book->setTitle('Lorem Ipsum');

        $reader = new ValidateReader();
        $reader->setId(1);
        $reader->setFirstName('John');
        $reader->setLastName('Smith');
        $reader->setEmail('jsmith@hermann.com');
        $reader->setBirthday('1974-11-19');
        $book->addValidateReader($reader);

        $res = $book->validate();

        $this->assertFalse($res, 'This validation expected to fail');

        $failures = $book->getValidationFailures();

        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $failures);
        $this->assertEquals(1, count($failures), 'Only one constraint violation object');

        $failure = $failures[0];
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolation', $failure);

        $failObject = new \ReflectionObject($failure->getRoot());

        $this->assertEquals('Propel\Tests\Bookstore\Behavior\ValidatePublisher', $failObject->getName(), 'Instance of ValidatePublisher expected to fail');
        $this->assertEquals('website', $failure->getPropertyPath(), 'website property expected to fail');
    }

    public function testComplexValidationMultipleFailures()
    {
        //Array of expected failures. key: property failed, value: Class in wich the property has failed
        $failedProperties = array(
            'first_name' => 'Propel\Tests\Bookstore\Behavior\ValidateAuthor',
            'website'    => 'Propel\Tests\Bookstore\Behavior\ValidatePublisher',
            'title'      => 'Propel\Tests\Bookstore\Behavior\ValidateBook',
            'email'      => 'Propel\Tests\Bookstore\Behavior\ValidateReader',
            'last_name'  => 'Propel\Tests\Bookstore\Behavior\ValidateReader'
        );

        $author = new ValidateAuthor();
        $author->setId(1);
        $author->setFirstName(null); //failed
        $author->setLastName('Friesen');
        $author->setEmail('of@hermann.com');
        $author->setBirthday('1954-10-16');

        $publisher = new ValidatePublisher();
        $publisher->setId(5);
        $publisher->setName('Huel Ltd');
        $publisher->setWebsite('huel.com'); //failed

        $book = new ValidateBook();
        $book->setId(1);
        $book->setValidateAuthor($author);
        $book->setValidatePublisher($publisher);
        $book->setTitle(null); //failed
        $book->setPrice(12,90);

        $reader1 = new ValidateReader();
        $reader1->setId(1);
        $reader1->setFirstName('Sigurd');
        $reader1->setLastName('Dare');
        $reader1->setEmail('sig.dare@'); //failed
        $reader1->setBirthday('1974-11-19');
        $book->addValidateReader($reader1);

        $reader2 = new ValidateReader();
        $reader2->setId(2);
        $reader2->setFirstName('Hans');
        $reader2->setLastName(null); //failed
        $reader2->setEmail('hwukert@klein.com');
        $reader2->setBirthday('1974-11-19');
        $book->addValidateReader($reader2);

        $res = $book->validate();

        $this->assertFalse($res, 'This validation expected to fail');

        $failures = $book->getValidationFailures();

        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $failures);
        $this->assertEquals(5, count($failures), 'Five constraint violation objects expected.');

        foreach ($failures as $failure) {
            $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolation', $failure);

            $failObject = new \ReflectionObject($failure->getRoot());

            $this->assertTrue(in_array($failure->getPropertyPath(), array_keys($failedProperties)));
            $this->assertEquals($failedProperties[$failure->getPropertyPath()], $failObject->getName());
        }
    }

}
