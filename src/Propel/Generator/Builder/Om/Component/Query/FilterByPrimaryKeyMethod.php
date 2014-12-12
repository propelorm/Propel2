<?php


namespace Propel\Generator\Builder\Om\Component\Query;


use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveQuery\ModelJoin;

/**
 * Adds all filterByPrimaryKey method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FilterByPrimaryKeyMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;


    public function process()
    {
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));
        $this->addFilterByPrimaryKey();
    }

    /**
     * Adds the filterByPrimaryKey method for this object.
     * @param string &$script The script will be modified in this method.
     */
    protected function addFilterByPrimaryKey()
    {
        if (!$this->getEntity()->hasPrimaryKey()) {
            return;
        }


        $body = '';
        $pks = $this->getEntity()->getPrimaryKey();
        if (1 === count($pks)) {
            // simple primary key
            $field = $pks[0];
            $const = $field->getFQConstantName();
            $body = "
return \$this->addUsingAlias($const, \$key, Criteria::EQUAL);";
        } else {
            // composite primary key
            $i = 0;
            foreach ($pks as $field) {
                $const = $field->getFQConstantName();
                $body = "
\$this->addUsingAlias($const, \$key[$i], Criteria::EQUAL);";
                $i++;
            }
            $body .= "

return \$this;";
        }

        $this->addMethod('filterByPrimaryKey')
            ->addSimpleDescParameter('key', 'mixed', 'Primary key to use for the query')
            ->setBody($body)
            ->setType("\$this|{$this->getQueryClassName()}")
            ->setTypeDescription('The current query, for fluid interface')
            ->setDescription('Filter the query by primary key.')
        ;
    }
}