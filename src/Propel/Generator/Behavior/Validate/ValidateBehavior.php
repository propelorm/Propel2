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
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Exception\ConstraintNotFoundException;

/**
 * Validate a model object using Symfony2 Validator component
 *
 * @author Cristiano Cinotti
*/
class ValidateBehavior extends Behavior
{
    /**
     * @param object $builder The current builder
     */
    protected $builder;

    /**
     * Add behavior methods to model class
     *
     * @return  string
     */
    public function objectMethods($builder)
    {
        $array = $this->getParameters();
        if (empty($array)) {
            throw new InvalidArgumentException('Please, define your rules for validation.');
        }
        $this->cleanupParameters();

        $this->builder = $builder;

        $this->builder->declareClasses('Symfony\\Component\\Validator\\Mapping\\ClassMetadata', 'Symfony\\Component\\Validator\\Validator', 'Symfony\\Component\\Validator\\Mapping\\Loader\\StaticMethodLoader', 'Symfony\\Component\\Validator\\ConstraintValidatorFactory', 'Symfony\\Component\\Validator\\Mapping\\ClassMetadataFactory', 'Symfony\\Component\\Validator\\ConstraintViolationList');

        $script = $this->addLoadValidatorMetadataMethod();
        $script .= $this->addValidateMethod();
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
     * Returns the parameters associated with a given column.
     * Useful for i18n behavior
     *
     * @param string The column name
     * @return array The array of parameters associated to given column
     */
    public function getParametersFromColumnName($columnName = '')
    {
        if ('' == $columnName) {

            return array();
        } else {
            $outArray = array();
            $this->cleanupParameters();
            $parameters = $this->getParameters();
            foreach ($parameters as $key=>$parameter) {
                if ($parameter['column'] == $columnName) {
                    $outArray[$key] = $parameter;
                }
            }

            return $outArray;
        }
    }

    /**
     * Remove parameters associated with given column.
     * Useful for i18n behavior
     *
     * @param  string  The column name
     */
    public function removeParametersFromColumnName($columnName = '')
    {
        if ($columnName != '') {
            $newParams = array();
            $parameters = $this->getParameters();
            foreach ($parameters as $key=>$parameter) {
                if ($parameter['column'] != $columnName) {
                    $newParams[$key] = $parameter;
                }
            }

            $this->setParameters($newParams);
        }
    }

    /**
     * Add a rule based on primary key type, if there aren't other parameters.
     * Useful when modify table (i18n behavior).
     * If all the rules have been removed, the behavior can't perform validation on related tables.
     * This method introduce a rule to avoid this.
     */
    public function addRuleOnPk()
    {
        if (count($this->getParameters()) <= 0) {
          $pk = $this->getTable()->getPrimaryKey();
          $rule['auto_rule']['column'] = $pk[0]->getName();
          $rule['auto_rule']['validator'] = 'Type';
          $rule['auto_rule']['options'] = array('type' => $pk[0]->getPhpType());

          $this->setParameters($rule);
        }
    }

    /**
     * Merge $paramArray array into parameters array.
     * This method avoid that there are rules with the same name, when adding parameters programmatically.
     * Useful for Concrete Inheritance behavior.
     */
    public function mergeParameters($paramsArray = null)
    {
        if (!is_null($paramsArray)) {
            $params = $this->getParameters();
            $outArray = array();
            $i = 1;
            foreach($params as $key=>$param) {
                $outArray["rule$i"] = $param;
                $i++;
            }
            foreach($paramsArray as $key=>$paramArray) {
                $outArray["rule$i"] = $paramArray;
                $i++;
            }

            $this->setParameters($outArray);
        }
    }

    /**
     * Convert those parameters, containing an array in YAML format
     * into a php array
     */
    protected function cleanupParameters()
    {
      $parser = new Parser();
      $params = $this->getParameters();
      foreach ($params as $key => $value) {
          if (is_string($value)) {
              $params[$key] = $parser->parse($value);
          }
      }
      $this->setParameters($params);
    }

    /**
     * Add loadValidatorMetadata() method
     *
     * @return string
     */
    protected function addLoadValidatorMetadataMethod()
    {
        $params = $this->getParameters();
        $constraints = array();

        foreach ($params as $key=>$properties)
        {
            if (!isset($properties['column'])) {
                throw new InvalidArgumentException('Please, define the column to validate.');
            }

            if (!isset($properties['validator'])) {
                throw new InvalidArgumentException('Please, define the validator constraint.');
            }

            if (!class_exists("Symfony\\Component\\Validator\\Constraints\\".$properties['validator'], true)) {
                if (!class_exists("Propel\\Runtime\\Validator\\Constraints\\".$properties['validator'], true)) {
                    throw new ConstraintNotFoundException('The constraint class '.$properties['validator'].' does not exist.');
                } else {
                    $classConstraint = "Propel\\Runtime\\Validator\\Constraints\\".$properties['validator'];
                }
            } else {
                $classConstraint = "Symfony\\Component\\Validator\\Constraints\\".$properties['validator'];
            }

            if (isset($properties['options'])) {
                if (!is_array($properties['options'])) {
                    throw new InvalidArgumentException('The options value, in <parameter> tag must be an array');
                }

                $properties['options'] = $this->arrayToString($properties['options']);
            }

            $constraints[] = $properties;
            $this->builder->declareClass($classConstraint);
        }

        return $this->renderTemplate('objectLoadValidatorMetadata', array('constraints' => $constraints));
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
        foreach ($array as $key => $value) {
            $string .= "'$key' => ";

            if (is_array($value)) {
                $string .= $this->arrayToString($value, true);
            }
            else {
                $string .= "'$value', ";
            }
        }
        $string .= ")";

        if ($deep) {
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
        $table = $this->getTable();
        $foreignKeys = $table->getForeignKeys();
        $hasForeignKeys = (count($foreignKeys) != 0);
        $aVarNames = array();
        $refFkVarNames = array();
        $collVarNames = array();

        if ($hasForeignKeys) {
            foreach ($foreignKeys as $fk) {
                $aVarNames[] = $this->builder->getFKVarName($fk);
            }
        }

        foreach ($table->getReferrers() as $refFK) {
            if ($refFK->isLocalPrimaryKey()) {
                $refFkVarNames[] = $this->builder->getPKRefFKVarName($refFK);
            }
            else {
                $collVarNames[] = $this->builder->getRefFKCollVarName($refFK);
            }
        }

        return $this->renderTemplate('objectValidate', array(
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
