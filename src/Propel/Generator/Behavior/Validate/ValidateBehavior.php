<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Behavior\Validate;

use Propel\Generator\Model\Behavior;
use Symfony\Component\Yaml\Parser;
use \InvalidArgumentException;

/**
 * Validate a model object using Symfony2 Validator component
 * 
 * @author Cristiano Cinotti
*/ 
class ValidateBehavior extends Behavior
{
     /**
      * @param array $parameters The parameters array
      * 
      * The parameters array has a structure like this:
      *   array(
      *     "rule1" => "{column: your_column_name, validator: your_validator_name, options: {message: your_error_message, ....}" 
      *     "rule2" => .......
      *      ......
      *     "rulen" =>
      *   )
      * The keys are arbitrary named, the values are strings representing an array of properties in YAML format.
      * Those properties are: 
      * column: the column to be validated
      * validator: Symfony2 validation constraint 
      * options: (optional) array of options according to the documentation of the constraint
      * @see http://symfony.com/doc/current/reference/constraints.html
      */
     protected $parameters = array();
     
     protected $builder;
  
     /**
      * Add behavior method to model class
      *
      * @return  string
      */      
     public function objectMethods($builder)
     {
         $array = $this->getParameters();
         if (empty($array))
         {
             throw new InvalidArgumentException('Please, define your rules for validation.');
         }
         
         $this->builder = $builder;
         
         $this->builder->declareClasses('Symfony\\Component\\Validator\\Mapping\\ClassMetadata', 'Symfony\\Component\\Validator\\Validator', 'Symfony\\Component\\Validator\\Mapping\\Loader\\StaticMethodLoader', 'Symfony\\Component\\Validator\\ConstraintValidatorFactory', 'Symfony\\Component\\Validator\\Mapping\\ClassMetadataFactory', 'Symfony\\Component\\Validator\\ConstraintViolationList');
         
         $script = $this->addLoadValidatorMetadataMethod();
         $script .= $this->addValidateMethod();
         $script .= $this->addDoValidateMethod();
         $script .= $this->addGetValidationFailuresMethod();
     
         return $script;
         
     }
     
     /**
      * Add behavior attributes to model class 
      *
      * @return string The code to be added to model class
      */
     public function objectAttributes()
     {
          
          return $this->renderTemplate('objectAttributes');
         
     }
     
     /**
      * Add a loadValidatorMetadata() method
      *
      * @return string
      */
     protected function addLoadValidatorMetadataMethod()
     {
         $script = "
/**
 * Configure validators constraints. The Validator object uses this method
 * to perform object validation.
 *
 * @param ClassMetadata \$metadata
 */
public static function loadValidatorMetadata(ClassMetadata \$metadata)
{\n";
     
         $yaml = new Parser();
         $params = $this->getParameters();
         foreach($params as $key => $value)
         {
             $properties = $yaml->parse($value);
             $script .= $this->addValidatorConstraint($properties);
         }
         $script .= "}";
         
         return $script;
         
      }
     
  /**
  * Add a validator constraint, based on an array of properties
  * 
  * @param array $properties
  * @return string The code to be added to model class
  */
  protected function addValidatorConstraint($properties)
  {
      if (!isset($properties['column']))
      {
          throw new InvalidArgumentException('Please, define the column to validate.');
      }
      
      if (!isset($properties['validator']))
      {
          throw new InvalidArgumentException('Please, define the validator constraint.');
      }
      
      if (!class_exists("Symfony\\Component\\Validator\\Constraints\\".$properties['validator'], true))
      {
           throw new InvalidArgumentException('The constraint class '.$properties['validator'].' does not exist.');
      }
      
      $this->builder->declareClass("Symfony\\Component\\Validator\\Constraints\\".$properties['validator']);
      $output = "    \$metadata->addPropertyConstraint('".$properties['column']."', new ".$properties['validator']."(";
      if (isset($properties['options']))
      {
          if (!is_array($properties['options']))
          {
              throw new InvalidArgumentException('The options value, in <parameter> tag must be an array (in Yaml format)');
          }
          
          $output .= $this->arrayToString($properties['options']);
      }
      
      $output .= "));\n";
            
      return $output;
      
  }
  
  /**
   * Convenience method that takes an array and gives a string representing its php definition.
   * This method will recurse into deeper arrays.
   * 
   * @param array    $array  Array to process
   * @param boolean  $deep  true if it's a recursive call
   * @return string  The php definition of input array
  */
  protected function arrayToString ($array, $deep = false)
  {
      $string = "array(";
      foreach ($array as $key => $value)
      {
          $string .= "'$key' => ";
          
          if (is_array($value))
          {
              $string .= $this->arrayToString($value, true);
          }
          else
          {
              $string .= "'$value', ";
          }
      }
      $string .= ")";
      
      if ($deep)
      {
          $string .= ", ";
      }
      
      return $string;
  
  }
  
    /**
     * Adds the validate() method.
     * @return    string  The code to be added to model class
     */
    protected function addValidateMethod()
    {
    
        return $this->renderTemplate('ObjectValidate');
        
    }

    /**
     * Adds the workhourse doValidate() method.
     * @return      string &$script The script will be modified in this method.
     */
    protected function addDoValidateMethod()
    {
        $table = $this->getTable();
        $foreignKeys = $table->getForeignKeys();
        $hasForeignKeys = (count($foreignKeys) != 0);
        $aVarNames = array();
        $refFkVarNames = array();
        $collVarNames = array();
        
        if ($hasForeignKeys)
        {
            foreach ($foreignKeys as $fk)
            {
                $aVarNames[] = $this->builder->getFKVarName($fk);            
            }
        }
        
        foreach ($table->getReferrers() as $refFK)
        {
            if ($refFK->isLocalPrimaryKey())
            {
                $refFkVarNames[] = $this->builder->getPKRefFKVarName($refFK);
            } 
            else
            {
                $collVarNames[] = $this->builder->getRefFKCollVarName($refFK);
            }
        }
        
        return $this->renderTemplate('objectDoValidate', array(
            'hasForeignKeys' => $hasForeignKeys,
            'aVarNames'      => $aVarNames,
            'refFkVarNames'  => $refFkVarNames,
            'collVarNames'   => $collVarNames
        ));
        
    }
    
    /**
     * Adds the getValidationFailures() method.
     * @param      string &$script The script will be modified in this method.
     */
    protected function addGetValidationFailuresMethod()
    {
        
        return $this->renderTemplate('objectGetValidationFailures');
        
    } 
  
  
}