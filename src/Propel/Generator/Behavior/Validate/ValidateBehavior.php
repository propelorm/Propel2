<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Validate;

use Propel\Generator\Exception\ConstraintNotFoundException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Behavior;
use Symfony\Component\Yaml\Parser;

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
     * @return string
     */
    public function objectMethods($builder)
    {
        $array = $this->getParameters();
        if (empty($array)) {
            throw new InvalidArgumentException('Please, define your rules for validation.');
        }
        $this->cleanupParameters();

        $this->builder = $builder;
        $this->builder->declareClasses(
            'Symfony\\Component\\Validator\\Mapping\\ClassMetadata',
            'Symfony\\Component\\Validator\\DefaultTranslator',
            'Symfony\\Component\\Validator\\Mapping\\Loader\\StaticMethodLoader',
            'Symfony\\Component\\Validator\\ConstraintValidatorFactory',
            'Symfony\\Component\\Validator\\Mapping\\ClassMetadataFactory',
            'Symfony\\Component\\Validator\\ConstraintViolationList'
        );

        //if SF >= 5.3 use new validator classes
        if(class_exists('Symfony\\Component\\Validator\\Validator\\LegacyValidator')){
            $this->builder->declareClasses(
                'Symfony\\Component\\Validator\\Validator\\LegacyValidator',
                'Symfony\\Component\\Validator\\Context\\ExecutionContextFactory',
                'Symfony\\Component\\Validator\\Validator\\ValidatorInterface'
            );
        }else{
            $this->builder->declareClasses(
                'Symfony\\Component\\Validator\\Validator',
                'Symfony\\Component\\Validator\\ValidatorInterface'
            );
        }

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
     * @param  string $columnName The column name
     * @return array  The array of parameters associated to given column
     */
    public function getParametersFromColumnName($columnName = null)
    {
        $array = array();
        if (null !== $columnName) {
            $this->cleanupParameters();
            foreach ($this->getParameters() as $key => $parameter) {
                if ($parameter['column'] === $columnName) {
                    $array[$key] = $parameter;
                }
            }
        }

        return $array;
    }

    /**
     * Remove parameters associated with given column.
     * Useful for i18n behavior
     *
     * @param string $columnName The column name
     */
    public function removeParametersFromColumnName($columnName = null)
    {
        if (null !== $columnName) {
            $newParams = array();
            $parameters = $this->getParameters();
            foreach ($parameters as $key => $parameter) {
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
        if (!count($this->getParameters())) {
            $pk = $this->getTable()->getPrimaryKey();
            $parameters = array('auto_rule' => array(
                'column'     => $pk[0]->getName(),
                'validators' => 'Type',
                'options'    => array(
                    'type'   => $pk[0]->getPhpType(),
                ),
            ));
            $this->setParameters($parameters);
        }
    }

    /**
     * Merge $paramArray array into parameters array.
     * This method avoid that there are rules with the same name, when adding parameters programmatically.
     * Useful for Concrete Inheritance behavior.
     */
    public function mergeParameters(array $params = null)
    {
        if (null !== $params) {
            $parameters = $this->getParameters();
            $out = array();
            $i = 1;
            foreach ($parameters as $key => $parameter) {
                $out["rule$i"] = $parameter;
                $i++;
            }
            foreach ($params as $key => $param) {
                $out["rule$i"] = $param;
                $i++;
            }

            $this->setParameters($out);
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

        foreach ($params as $key => $properties) {
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

                $opt = var_export($properties['options'], true);
                $opt = str_replace("\n", '', $opt);
                $opt = str_replace('  ', '', $opt);
                $properties['options'] = $opt;
            }

            $constraints[] = $properties;
            $this->builder->declareClass($classConstraint);
        }

        return $this->renderTemplate('objectLoadValidatorMetadata', array('constraints' => $constraints));
    }

    /**
     * Adds the validate() method.
     * @return string The code to be added to model class
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
            } else {
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
     */
    protected function addGetValidationFailuresMethod()
    {
        return $this->renderTemplate('objectGetValidationFailures');
    }

}
