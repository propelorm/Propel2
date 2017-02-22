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
        //if SF >= 2.5 use new validator classes
        if(class_exists('Symfony\\Component\\Validator\\Validator\\RecursiveValidator')) {
            $this->metadataFactory = new \Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory(new StaticMethodLoader());
        } else {
            $this->metadataFactory = new \Symfony\Component\Validator\Mapping\ClassMetadataFactory(new StaticMethodLoader());
        }

    }

    public function testI18nBehaviorHandlesValidateBehavior()
    {
        $class = 'Propel\Tests\Bookstore\Behavior\ValidateTriggerBook';
        $this->checkClassHasValidateBehavior($class);

        $classMetadata = $this->metadataFactory->getMetadataFor($class);
        $this->assertCount(1, $classMetadata->getConstrainedProperties());
        $this->assertTrue(in_array('isbn', $classMetadata->getConstrainedProperties(), true));

        $metadatas = $classMetadata->getPropertyMetadata('isbn');
        $this->assertCount(1, $metadatas);

        $constraints = $metadatas[0]->getConstraints();
        $this->assertCount(1, $constraints);

        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\Regex', $constraints[0]);

        $i18nClass = 'Propel\Tests\Bookstore\Behavior\ValidateTriggerBookI18n';
        $this->checkClassHasValidateBehavior($i18nClass);

        $i18nClassMetadata = $this->metadataFactory->getMetadataFor($i18nClass);
        $this->assertCount(1, $i18nClassMetadata->getConstrainedProperties());
        $this->assertTrue(in_array('title', $i18nClassMetadata->getConstrainedProperties(), true));

        $i18nMetadatas = $i18nClassMetadata->getPropertyMetadata('title');
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

        $fictionMetadatas = $fictionMetadata->getPropertyMetadata('isbn');

        // 1st is for ValidateTriggerFiction (base)
        // 2nd is for ValidateTriggerBook (base)
        // I'm not sure if this is needed. We should not care about validator internals
        $this->assertCount(2, $fictionMetadatas);

        $expectedValidatorGroups = array(
            'ValidateTriggerFiction',
            'ValidateTriggerBook',
        );

        // iterate over metadatas and constarints.
        // If constraint match with expected constraint -> remove it form expectations list
        // We are looking for our regex validations
        foreach ($fictionMetadatas as $fictionmetadata) {
            /* @var $constraint \Symfony\Component\Validator\Mapping\PropertyMetadata */
            foreach ($fictionmetadata->getConstraints() as $constraint) {
                if ($constraint instanceof \Symfony\Component\Validator\Constraints\Regex) {
                    $expectedValidatorGroups = array_diff($expectedValidatorGroups, $constraint->groups);
                }
            }
        }

        $this->assertEmpty($expectedValidatorGroups);

        $comic = 'Propel\Tests\Bookstore\Behavior\ValidateTriggerComic';

        $this->checkClassHasValidateBehavior($comic);

        $comicMetadata = $this->metadataFactory->getMetadataFor($comic);
        $this->assertCount(2, $comicMetadata->getConstrainedProperties());
        $this->assertTrue(in_array('isbn', $comicMetadata->getConstrainedProperties(), true));
        $this->assertTrue(in_array('bar', $comicMetadata->getConstrainedProperties(), true));

        $comicMetadatas['isbn'] = $comicMetadata->getPropertyMetadata('isbn');
        $comicMetadatas['bar']  = $comicMetadata->getPropertyMetadata('bar');

        $expectedComicValidators = array(
            'ValidateTriggerComic',
            'ValidateTriggerComic',
            'ValidateTriggerBook',
        );

        foreach ($comicMetadatas['isbn'] as $metadata) {
            /* @var $metadata \Symfony\Component\Validator\Mapping\PropertyMetadata */
            foreach ($metadata->getConstraints() as $constraint) {
                if ($constraint instanceof \Symfony\Component\Validator\Constraints\Regex) {
                    $expectedComicValidators = array_diff($expectedComicValidators, $constraint->groups);
                }
            }
        }

        $this->assertEmpty($expectedComicValidators);

        $comicMetadataBar = $comicMetadatas['bar'];

        $expectedComicBarValidatorTypes = array(
            0 => 'Symfony\Component\Validator\Constraints\NotNull',
            1 => 'Symfony\Component\Validator\Constraints\Type',
        );
        foreach ($comicMetadataBar as $metadata) {
            $constraints = $metadata->getConstraints();
            foreach ($constraints as $constraint) {
                if ($constraint instanceof \Symfony\Component\Validator\Constraints\NotNull) {
                    unset($expectedComicBarValidatorTypes[0]);
                } elseif ($constraint instanceof \Symfony\Component\Validator\Constraints\Type) {
                    unset($expectedComicBarValidatorTypes[1]);
                }
            }
        }

        $this->assertEmpty($expectedComicBarValidatorTypes);
    }

    public function testConcreteInheritanceAndI18nBehaviorHandlesValidateBehavior()
    {
        $classes = ['ValidateTriggerFictionI18n', 'ValidateTriggerComicI18n'];

        foreach ($classes as $class) {
            $this->checkClassHasValidateBehavior('Propel\Tests\Bookstore\Behavior\\'.$class);

            $classMetadata = $this->metadataFactory->getMetadataFor('Propel\Tests\Bookstore\Behavior\\'.$class);
            $this->assertCount(1, $classMetadata->getConstrainedProperties());
            $this->assertTrue(in_array('title', $classMetadata->getConstrainedProperties(), true));

            $metadatas = $classMetadata->getPropertyMetadata('title');
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
