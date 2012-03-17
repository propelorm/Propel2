<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Behavior\Validate;

use Propel\Generator\Behavior\Validate\ValidateBehavior;
use Propel\Tests\Bookstore\Behavior;
use Propel\Runtime\Propel;

/**
 * Tests for ValidateBehavior class
 *
 * @author     Cristiano Cinotti
 */
class ValidateBehaviorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param  array   Instances of ValidateAuthor, ValidateBook, ValidatePublisher, ValidateReader classes. 
     *                 This classes are created by test:prepare command
     */
    private $objects;

    public function assertPreConditions()
    {
        if (!class_exists('Propel\Tests\Bookstore\Behavior\ValidateAuthor')) 
        {
            throw new \Exception('Please, run \'bin/propel test:prepare\' command before starting to test this behavior');
        }
        
        $this->objects['ValidateAuthor'] = new Behavior\ValidateAuthor();
        $this->objects['ValidateBook'] = new Behavior\ValidateBook();
        $this->objects['ValidatePublisher'] = new Behavior\ValidatePublisher();
        $this->objects['ValidateReader'] = new Behavior\ValidateReader();
        $this->objects['ValidateReaderBook'] = new Behavior\ValidateReaderBook();
        
    }
    
    public function testHasValidateMethod()
    {
        foreach ($this->objects as $key=>$object)
        {
             $this->assertTrue(method_exists($object, 'validate'));
        }
    }
    
    public function testHasDoValidateMethod()
    {
        foreach ($this->objects as $key=>$object)
        {
             $this->assertTrue(method_exists($object, 'doValidate'));
        }
    }
    
    public function testHasLoadValidatorMetadataMethod()
    {
        foreach ($this->objects as $key=>$object)
        {
             $this->assertTrue(method_exists($object, 'loadValidatorMetadata'));
        }
    }
    
    public function testHasAlreadyInValidationAttribute()
    {
        foreach ($this->objects as $key=>$object)
        {
             $this->assertObjectHasAttribute('alreadyInValidation', $object);
        }
    }
    
    public function testHasValidationFailuresAttribute()
    {
        foreach ($this->objects as $key=>$object)
        {
             $this->assertObjectHasAttribute('validationFailures', $object);
        }
    }
    
    public function testLoadValidatorMetadataMethodIsStatic()
    {
        foreach ($this->objects as $key=>$object)
        {
             $method = new \ReflectionMethod($object, 'loadValidatorMetadata');
             $this->assertTrue($method->isStatic());
        }
    }
    
    
  }