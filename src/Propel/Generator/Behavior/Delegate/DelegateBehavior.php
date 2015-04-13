<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Delegate;

use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Util\PhpParser;
use Propel\Runtime\Exception\PropelException;

/**
 * Gives a model class the ability to delegate methods to a relationship.
 *
 * @author François Zaninotto
 */
class DelegateBehavior extends Behavior
{
    const ONE_TO_ONE = 1;
    const MANY_TO_ONE = 2;

    // default parameters value
    protected $parameters = array(
        'to' => ''
    );

    protected $delegates = array();

    /**
     * Lists the delegates and checks that the behavior can use them,
     * And adds a fk from the delegate to the main table if not already set
     */
    public function modifyTable()
    {
        $table = $this->getTable();
        $database = $table->getDatabase();
        $delegates = explode(',', $this->parameters['to']);
        foreach ($delegates as $delegate) {
            $delegate = $database->getTablePrefix() . trim($delegate);
            if (!$database->hasTable($delegate)) {
                throw new \InvalidArgumentException(sprintf(
                    'No delegate table "%s" found for table "%s"',
                    $delegate,
                    $table->getName()
                ));
            }
            if (in_array($delegate, $table->getForeignTableNames())) {
                // existing many-to-one relationship
                $type = self::MANY_TO_ONE;
            } else {
                // one_to_one relationship
                $delegateTable = $this->getDelegateTable($delegate);
                if (in_array($table->getName(), $delegateTable->getForeignTableNames())) {
                    // existing one-to-one relationship
                    $fks = $delegateTable->getForeignKeysReferencingTable($this->getTable()->getName());
                    $fk = $fks[0];
                    if (!$fk->isLocalPrimaryKey()) {
                        throw new \InvalidArgumentException(sprintf(
                            'Delegate table "%s" has a relationship with table "%s", but it\'s a one-to-many relationship. The `delegate` behavior only supports one-to-one relationships in this case.',
                            $delegate,
                            $table->getName()
                        ));
                    }
                } else {
                    // no relationship yet: must be created
                    $this->relateDelegateToMainTable($this->getDelegateTable($delegate), $table);
                }
                $type = self::ONE_TO_ONE;
            }
            $this->delegates[$delegate] = $type;
        }
    }

    protected function relateDelegateToMainTable($delegateTable, $mainTable)
    {
        $pks = $mainTable->getPrimaryKey();
        foreach ($pks as $column) {
            $mainColumnName = $column->getName();
            if (!$delegateTable->hasColumn($mainColumnName)) {
                $column = clone $column;
                $column->setAutoIncrement(false);
                $delegateTable->addColumn($column);
            }
        }
        // Add a one-to-one fk
        $fk = new ForeignKey();
        $fk->setForeignTableCommonName($mainTable->getCommonName());
        $fk->setForeignSchemaName($mainTable->getSchema());
        $fk->setDefaultJoin('LEFT JOIN');
        $fk->setOnDelete(ForeignKey::CASCADE);
        $fk->setOnUpdate(ForeignKey::NONE);
        foreach ($pks as $column) {
            $fk->addReference($column->getName(), $column->getName());
        }
        $delegateTable->addForeignKey($fk);
    }

    protected function getDelegateTable($delegateTableName)
    {
        return $this->getTable()->getDatabase()->getTable($delegateTableName);
    }

    public function objectCall($builder)
    {
        $plural = false;
        $script = '';
        foreach ($this->delegates as $delegate => $type) {
            $delegateTable = $this->getDelegateTable($delegate);
            if ($type == self::ONE_TO_ONE) {
                $fks = $delegateTable->getForeignKeysReferencingTable($this->getTable()->getName());
                $fk = $fks[0];
                $ARClassName = $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($fk->getTable()));
                $ARFQCN = $builder->getNewStubObjectBuilder($fk->getTable())->getFullyQualifiedClassName();
                $relationName = $builder->getRefFKPhpNameAffix($fk, $plural);
            } else {
                $fks = $this->getTable()->getForeignKeysReferencingTable($delegate);
                $fk = $fks[0];
                $ARClassName = $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($delegateTable));
                $ARFQCN = $builder->getNewStubObjectBuilder($delegateTable)->getFullyQualifiedClassName();
                $relationName = $builder->getFKPhpNameAffix($fk);
            }
                $script .= "
if (is_callable(array('$ARFQCN', \$name))) {
    if (!\$delegate = \$this->get$relationName()) {
        \$delegate = new $ARClassName();
        \$this->set$relationName(\$delegate);
    }

    return call_user_func_array(array(\$delegate, \$name), \$params);
}";
        }

        return $script;
    }

    public function objectFilter(&$script)
    {
        $p = new PhpParser($script, true);
        $text = $p->findMethod('toArray', true);
        $matches = [];
        preg_match('/(\$result = array\(([^;]+)\);)/U', $text, $matches);
        $values = rtrim($matches[2]) . "\n";
        $new_result = '';
        $indent = '        ';

        foreach ($this->delegates as $key => $value) {
            $delegateTable = $this->getDelegateTable($key);

            $ns = $delegateTable->getNamespace() ? '\\'.$delegateTable->getNamespace() : '';
            $new_result .= "\$keys_{$key} = {$ns}\\Map\\{$delegateTable->getPhpName()}TableMap::getFieldNames(\$keyType);\n";
            $i = 0;
            foreach ($delegateTable->getColumns() as $column) {
                if (!$this->isColumnForeignKeyOrDuplicated($column)) {
                    $values .= "{$indent}    \$keys_{$key}[{$i}] => \$this->get{$column->getPhpName()}(),\n";
                }
                $i++;
            }
        }

        $new_result .= "{$indent}\$result = array({$values}\n{$indent});";
        $text = str_replace($matches[1], $new_result , $text);
        $p->replaceMethod('toArray', $text);
        $script = $p->getCode();

        return $script;
    }

    /**
     * @param Column $column
     *
     * @return bool
     *
     * @throws PropelException
     */
    protected function isColumnForeignKeyOrDuplicated(Column $column)
    {
        $delegateTable = $column->getTable();
        $table = $this->getTable();

        $fks = [];
        foreach ($delegateTable->getForeignKeysReferencingTable($table->getName()) as $fk) {
            /** @var \Propel\Generator\Model\ForeignKey $fk */
            $fks[] = $fk->getForeignColumnName();
        }
        foreach ($table->getForeignKeysReferencingTable($delegateTable->getName()) as $fk) {
            $fks[] = $fk->getForeignColumnName();
        }

        if (in_array($column->getName(), $fks)) {
            return true;
        } else {
            if ($table->hasColumn($column->getName())) {
                throw new PropelException('Column with name «'.$column->getName().'» (delegated from table «'.$delegateTable->getName().'») already exists in table «'.$table->getName().'». Probably database design mistake');
            }

            return false;
        }
    }

    public function queryAttributes()
    {
        $script = '';
        $collations = '';

        foreach ($this->delegates as $delegate => $type) {
            $delegateTable = $this->getDelegateTable($delegate);

            foreach ($delegateTable->getColumns() as $column) {
                if (!$this->isColumnForeignKeyOrDuplicated($column)) {
                    $collations .= "    '{$column->getPhpName()}' => '{$delegateTable->getPhpName()}',\n";
                }
            }
        }

        if ($collations) {
            $collations = substr($collations, 0, -1);
            $script .= "
protected \$delegatedFields = [
{$collations}
];

";
        }

        return $script;
    }

    public function queryMethods(QueryBuilder $builder)
    {
        $script = '';

        foreach ($this->delegates as $delegate => $type) {
            $delegateTable = $this->getDelegateTable($delegate);

            foreach ($delegateTable->getColumns() as $column) {
                if (!$this->isColumnForeignKeyOrDuplicated($column)) {
                    $phpName = $column->getPhpName();
                    $fieldName = $column->getName();
                    $tablePhpName = $delegateTable->getPhpName();
                    $childClassName = 'Child' . $builder->getUnprefixedClassName();

                    $script .= $this->renderTemplate('queryMethodsTemplate', compact('tablePhpName', 'phpName', 'childClassName', 'fieldName'));
                }

            }
        }

        if ($this->delegates) {
            $script .= "
/**
 * Adds a condition on a column based on a column phpName and a value
 * Uses introspection to translate the column phpName into a fully qualified name
 * Warning: recognizes only the phpNames of the main Model (not joined tables)
 * <code>
 * \$c->filterBy('Title', 'foo');
 * </code>
 *
 * @see Criteria::add()
 *
 * @param string \$column     A string representing thecolumn phpName, e.g. 'AuthorId'
 * @param mixed  \$value      A value for the condition
 * @param string \$comparison What to use for the column comparison, defaults to Criteria::EQUAL
 *
 * @return \$this|ModelCriteria The current object, for fluid interface
 */
public function filterBy(\$column, \$value, \$comparison = Criteria::EQUAL)
{
    if (isset(\$this->delegatedFields[\$column])) {
        \$methodUse = \"use{\$this->delegatedFields[\$column]}Query\";

        return \$this->{\$methodUse}()->filterBy(\$column, \$value, \$comparison)->endUse();
    } else {
        return \$this->add(\$this->getRealColumnName(\$column), \$value, \$comparison);
    }
}
";
        }

       return $script;
    }
}
