<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Validate;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;

/**
 * Tests for interaction between I18n behavior, ConcreteInheritance behavior
 * and Validate behavior.
 *
 * @author Cristiano Cinotti
 *
 * @group database
 */
class I18nConcreteInheritanceHandleValidateBehaviorTest extends BookstoreTestBase
{
    protected $metadataFactory;

    public function assertPreConditions()
    {
        $this->metadataFactory = new ClassMetadataFactory(new StaticMethodLoader());
    }

    public function testI18nBehaviorHandlesValidateBehavior()
    {
        $class = 'Propel\Tests\Bookstore\Behavior\ValidateTriggerBook';
        $this->checkClassHasValidateBehavior($class);

        $classMetadata = $this->metadataFactory->getMetadataFor($class);
        $this->assertCount(1, $classMetadata->getConstrainedProperties());
        $this->assertTrue(in_array('isbn', $classMetadata->getConstrainedProperties(), true));

        $metadatas = $classMetadata->getMemberMetadatas('isbn');
        $this->assertCount(1, $metadatas);

        $constraints = $metadatas[0]->getConstraints();
        $this->assertCount(1, $constraints);

        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\Regex', $constraints[0]);

        $i18nClass = 'Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18n';
        $this->checkClassHasValidateBehavior($i18nClass);

        $i18nClassMetadata = $this->metadataFactory->getMetadataFor($i18nClass);
        $this->assertCount(1, $i18nClassMetadata->getConstrainedProperties());
        $this->assertTrue(in_array('title', $i18nClassMetadata->getConstrainedProperties(), true));

        $i18nMetadatas = $i18nClassMetadata->getMemberMetadatas('title');
        $this->assertCount(1, $i18nMetadatas);

        $i18nConstraints = $i18nMetadatas[0]->getConstraints();
        $this->assertCount(1, $i18nConstraints);

        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\NotNull', $i18nConstraints[0]);
    }

    public function testConcreteInheritanceBehaviorHandlesValidateBehavior()
    {
        $fiction = 'Propel\Tests\Bookstore\Behavior\ValidateTriggerFiction';

        $this->checkClassHasValidateBehavior($fiction);

        $fictionMetadata = $this->metadataFactory->getMetadataFor($fiction);
        $this->assertCount(1, $fictionMetadata->getConstrainedProperties());
        $this->assertTrue(in_array('isbn', $fictionMetadata->getConstrainedProperties(), true));

        $fictionMetadatas = $fictionMetadata->getMemberMetadatas('isbn');
        $this->assertCount(1, $fictionMetadatas);

        $fictionConstraints = $fictionMetadatas[0]->getConstraints();
        $this->assertCount(2, $fictionConstraints);
        $this->assertTrue(in_array('ValidateTriggerFiction', $fictionConstraints[0]->groups));
        $this->assertTrue(in_array('ValidateTriggerBook', $fictionConstraints[0]->groups));
        $this->assertTrue(in_array('ValidateTriggerFiction', $fictionConstraints[1]->groups));
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\Regex', $fictionConstraints[0]);
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\Regex', $fictionConstraints[1]);

        $comic = 'Propel\Tests\Bookstore\Behavior\ValidateTriggerComic';

        $this->checkClassHasValidateBehavior($comic);

        $comicMetadata = $this->metadataFactory->getMetadataFor($comic);
        $this->assertCount(2, $comicMetadata->getConstrainedProperties());
        $this->assertTrue(in_array('isbn', $comicMetadata->getConstrainedProperties(), true));
        $this->assertTrue(in_array('bar', $comicMetadata->getConstrainedProperties(), true));

        $comicMetadatas['isbn'] = $comicMetadata->getMemberMetadatas('isbn');
        $comicMetadatas['bar']  = $comicMetadata->getMemberMetadatas('bar');
        $this->assertCount(1, $comicMetadatas['isbn']);
        $this->assertCount(1, $comicMetadatas['bar']);

        $comicConstraintsIsbn = $comicMetadatas['isbn'][0]->getConstraints();
        $this->assertCount(2, $comicConstraintsIsbn);
        $this->assertTrue(in_array('ValidateTriggerComic', $comicConstraintsIsbn[0]->groups));
        $this->assertTrue(in_array('ValidateTriggerBook', $comicConstraintsIsbn[0]->groups));
        $this->assertTrue(in_array('ValidateTriggerComic', $comicConstraintsIsbn[1]->groups));
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\Regex', $comicConstraintsIsbn[0]);
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\Regex', $comicConstraintsIsbn[1]);

        $comicConstraintsBar = $comicMetadatas['bar'][0]->getConstraints();
        $this->assertCount(2,$comicConstraintsBar);
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\NotNull', $comicConstraintsBar[0]);
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\Type', $comicConstraintsBar[1]);
    }

    public function testConcreteInheritanceAndI18nBehaviorHandlesValidateBehavior()
    {
        $classes = array('ValidateTriggerFictionI18n', 'ValidateTriggerComicI18n');

        foreach ($classes as $class) {
            $this->checkClassHasValidateBehavior('Propel\Tests\Bookstore\Behavior\\'.$class);

            $classMetadata = $this->metadataFactory->getMetadataFor('Propel\Tests\Bookstore\Behavior\\'.$class);
            $this->assertCount(1, $classMetadata->getConstrainedProperties());
            $this->assertTrue(in_array('title', $classMetadata->getConstrainedProperties(), true));

            $metadatas = $classMetadata->getMemberMetadatas('title');
            $this->assertCount(1, $metadatas);

            $constraints = $metadatas[0]->getConstraints();
            $this->assertCount(1, $constraints);

            $this->assertInstanceOf('Symfony\Component\Validator\Constraints\NotNull', $constraints[0]);
        }
    }

    protected function checkClassHasValidateBehavior($class)
    {
        $this->assertTrue(method_exists($class, 'validate'), "Class $class has no validate() method");
        $this->assertTrue(method_exists($class, 'getValidationFailures'), "Class $class has no getValidationFailures() method");
        $this->assertTrue(method_exists($class, 'loadValidatorMetadata'), "Class $class has no loadValidatorMetadata() method");
        $this->assertClassHasAttribute('alreadyInValidation', $class, "Class $class has no 'alreadyInValidation' property");
        $this->assertClassHasAttribute('validationFailures', $class, "Class $class has no 'validationFailures' property");
        $method = new \ReflectionMethod($class, 'loadValidatorMetadata');
        $this->assertTrue($method->isStatic(), "Method loadValidatorMetadata() of class $class isn't static");
    }
}
