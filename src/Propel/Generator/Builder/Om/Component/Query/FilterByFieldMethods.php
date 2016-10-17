<?php


namespace Propel\Generator\Builder\Om\Component\Query;


use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;

/**
 * Adds all filterBy methods for fields.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FilterByFieldMethods extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;


    public function process()
    {
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));

        foreach ($this->getEntity()->getFields() as $field) {
            $this->addFilterByCol($field);
            if ($field->getType() === PropelTypes::PHP_ARRAY && $field->isNamePlural()) {
                $this->addFilterByArrayCol($field);
            }
        }
    }

    /**
     * Adds the filterByCol method for this object.
     *
     * @param Field $field
     */
    protected function addFilterByCol(Field $field)
    {
        $fieldMethodName = $field->getMethodName();
        $fieldName = $field->getName();
        $qualifiedName = $field->getFQConstantName();

        $variableParameter = new PhpParameter($fieldName);
        $variableParameter->setType('mixed');
        $variableParameter->setTypeDescription('The value to use as filter.');

        $description = "
Filter the query on the $fieldName field.
";
        if ($field->isNumericType()) {
            $description .= "
Example usage:
<code>
\$query->filterBy$fieldMethodName(1234); // WHERE $fieldName = 1234
\$query->filterBy$fieldMethodName(array(12, 34)); // WHERE $fieldName IN (12, 34)
\$query->filterBy$fieldMethodName(array('min' => 12)); // WHERE $fieldName > 12
</code>";
            if ($field->isRelation()) {
                foreach ($field->getRelations() as $relation) {
                    $description .= "
     *
    @see filterBy" . $this->getRelationName($relation) . "()";
                }
            }

            $variableParameter->setTypeDescription("The value to use as filter.
Use scalar values for equality.
Use array values for in_array() equivalent.
Use associative array('min' => \$minValue, 'max' => \$maxValue) for intervals.");
        } elseif ($field->isTemporalType()) {

            $variableParameter->setTypeDescription("The value to use as filter.
Values can be integers (unix timestamps), DateTime objects, or strings.
Empty strings are treated as NULL.
Use scalar values for equality.
Use array values for in_array() equivalent.
Use associative array('min' => \$minValue, 'max' => \$maxValue) for intervals.");

            $description .= "
Example usage:
<code>
\$query->filterBy$fieldMethodName('2011-03-14'); // WHERE $fieldName = '2011-03-14'
\$query->filterBy$fieldMethodName('now'); // WHERE $fieldName = '2011-03-14'
\$query->filterBy$fieldMethodName(array('max' => 'yesterday')); // WHERE $fieldName > '2011-03-13'
</code>";
        } elseif ($field->getType() == PropelTypes::PHP_ARRAY) {

            $variableParameter->setType('array');
            $variableParameter->setTypeDescription("The value to use as filter.");
        } elseif ($field->isTextType()) {

            $variableParameter->setType('string');
            $variableParameter->setTypeDescription("The value to use as filter.
 Accepts wildcards (* and % trigger a LIKE)");


            $description .= "
Example usage:
<code>
\$query->filterBy$fieldMethodName('fooValue');   // WHERE $fieldName = 'fooValue'
\$query->filterBy$fieldMethodName('%fooValue%'); // WHERE $fieldName LIKE '%fooValue%'
</code>";

        } elseif ($field->isBooleanType()) {

            $variableParameter->setType('boolean|string');
            $variableParameter->setTypeDescription("The value to use as filter.
 Non-boolean arguments are converted using the following rules:
            1, '1', 'true',  'on',  and 'yes' are converted to boolean true
  0, '0', 'false', 'off', and 'no'  are converted to boolean false
 Check on string values is case insensitive (so 'FaLsE' is seen as 'false').");

            $description .= "
Example usage:
<code>
\$query->filterBy$fieldMethodName(true); // WHERE $fieldName = true
\$query->filterBy$fieldMethodName('yes'); // WHERE $fieldName = true
</code>";

        }

        $body = '';

        if ($field->isNumericType() || $field->isTemporalType()) {
            $body .= "
if (is_array(\$$fieldName)) {
    \$useMinMax = false;
    if (isset(\${$fieldName}['min'])) {
        \$this->addUsingAlias($qualifiedName, \${$fieldName}['min'], Criteria::GREATER_EQUAL);
        \$useMinMax = true;
    }
    if (isset(\${$fieldName}['max'])) {
        \$this->addUsingAlias($qualifiedName, \${$fieldName}['max'], Criteria::LESS_EQUAL);
        \$useMinMax = true;
    }
    if (\$useMinMax) {
        return \$this;
    }
    if (null === \$comparison) {
        \$comparison = Criteria::IN;
    }
}";
        } elseif ($field->getType() == PropelTypes::OBJECT) {
            $body .= "
if (is_object(\$$fieldName)) {
    \$$fieldName = serialize(\$$fieldName);
}";
        } elseif ($field->getType() == PropelTypes::PHP_ARRAY) {
            $body .= "
\$key = \$this->getAliasedColName($qualifiedName);
if (null === \$comparison || \$comparison == Criteria::CONTAINS_ALL) {
    foreach (\$$fieldName as \$value) {
        \$value = '%| ' . \$value . ' |%';
        if (\$this->containsKey(\$key)) {
            \$this->addAnd(\$key, \$value, Criteria::LIKE);
        } else {
            \$this->add(\$key, \$value, Criteria::LIKE);
        }
    }

    return \$this;
} elseif (\$comparison == Criteria::CONTAINS_SOME) {
    foreach (\$$fieldName as \$value) {
        \$value = '%| ' . \$value . ' |%';
        if (\$this->containsKey(\$key)) {
            \$this->addOr(\$key, \$value, Criteria::LIKE);
        } else {
            \$this->add(\$key, \$value, Criteria::LIKE);
        }
    }

    return \$this;
} elseif (\$comparison == Criteria::CONTAINS_NONE) {
    foreach (\$$fieldName as \$value) {
        \$value = '%| ' . \$value . ' |%';
        if (\$this->containsKey(\$key)) {
            \$this->addAnd(\$key, \$value, Criteria::NOT_LIKE);
        } else {
            \$this->add(\$key, \$value, Criteria::NOT_LIKE);
        }
    }
    \$this->addOr(\$key, null, Criteria::ISNULL);

    return \$this;
}";
        } elseif ($field->getType() == PropelTypes::ENUM) {
            $body .= "
\$valueSet = " . $this->getEntityMapClassName() . "::getValueSet(" . $field->getConstantName() . ");
if (is_scalar(\$$fieldName)) {
    if (!in_array(\$$fieldName, \$valueSet)) {
        throw new PropelException(sprintf('Value \"%s\" is not accepted in this enumerated column', \$$fieldName));
    }
    \$$fieldName = array_search(\$$fieldName, \$valueSet);
} elseif (is_array(\$$fieldName)) {
    \$convertedValues = array();
    foreach (\$$fieldName as \$value) {
        if (!in_array(\$value, \$valueSet)) {
            throw new PropelException(sprintf('Value \"%s\" is not accepted in this enumerated column', \$value));
        }
        \$convertedValues []= array_search(\$value, \$valueSet);
    }
    \$$fieldName = \$convertedValues;
    if (null === \$comparison) {
        \$comparison = Criteria::IN;
    }
}";
        } elseif ($field->isTextType()) {
            $body .= "
if (null === \$comparison) {
    if (is_array(\$$fieldName)) {
        \$comparison = Criteria::IN;
    } elseif (preg_match('/[\%\*]/', \$$fieldName)) {
        \$$fieldName = str_replace('*', '%', \$$fieldName);
        \$comparison = Criteria::LIKE;
    }
}";
        } elseif ($field->isBooleanType()) {
            $body .= "
if (is_string(\$$fieldName)) {
    \$$fieldName = in_array(strtolower(\$$fieldName), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
}";
        }
        $body .= "

return \$this->addUsingAlias($qualifiedName, \$$fieldName, \$comparison);
";

        $methodName = "filterBy$fieldMethodName";

        $this->addMethod($methodName)
            ->addParameter($variableParameter)
            ->addSimpleDescParameter('comparison', 'string', 'Operator to use for the column comparison, defaults to Criteria::EQUAL', null)
            ->setDescription($description)
            ->setType("\$this|" . $this->getQueryClassName())
            ->setTypeDescription("The current query, for fluid interface")
            ->setBody($body);
    }

    /**
     * Adds the singular filterByCol method for an Array column.
     *
     * @param Field $field
     */
    protected function addFilterByArrayCol(Field $field)
    {
        $singularName = $field->getSingularName();
        $fieldName = $field->getName();
        $qualifiedName = $field->getFQConstantName();

        $description = "Filter the query on the $fieldName column";

        $body = "
if (null === \$comparison || \$comparison == Criteria::CONTAINS_ALL) {
    if (is_scalar(\$$fieldName)) {
        \$$fieldName = '%| ' . \$$fieldName . ' |%';
        \$comparison = Criteria::LIKE;
    }
} elseif (\$comparison == Criteria::CONTAINS_NONE) {
    \$$fieldName = '%| ' . \$$fieldName . ' |%';
    \$comparison = Criteria::NOT_LIKE;
    \$key = \$this->getAliasedColName($qualifiedName);
    if (\$this->containsKey(\$key)) {
        \$this->addAnd(\$key, \$$fieldName, \$comparison);
    } else {
        \$this->addAnd(\$key, \$$fieldName, \$comparison);
    }
    \$this->addOr(\$key, null, Criteria::ISNULL);

    return \$this;
}

return \$this->addUsingAlias($qualifiedName, \$$fieldName, \$comparison);
";

        $variableParameter = new PhpParameter($fieldName);
        $variableParameter->setDefaultValue(null);

        $methodName = "filterBy$singularName";

        $this->addMethod($methodName)
            ->addParameter($variableParameter)
            ->addSimpleDescParameter('comparison', 'string', 'Operator to use for the column comparison, defaults to Criteria::EQUAL', null)
            ->setDescription($description)
            ->setType("\$this|" . $this->getQueryClassName())
            ->setTypeDescription("The current query, for fluid interface")
            ->setBody($body);
    }
}