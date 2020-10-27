<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Delegate;

use InvalidArgumentException;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\NameGeneratorInterface;
use Propel\Generator\Util\PhpParser;

/**
 * Gives a model class the ability to delegate methods to a relationship.
 *
 * @author François Zaninotto
 */
class DelegateBehavior extends Behavior
{
    public const ONE_TO_ONE = 1;
    public const MANY_TO_ONE = 2;

    /**
     * Default parameters value
     *
     * @var string[]
     */
    protected $parameters = [
        'to' => '',
    ];

    /**
     * @var int[]
     */
    protected $delegates = [];

    /**
     * @var array|null
     */
    protected $double_defined;

    /**
     * Lists the delegates and checks that the behavior can use them,
     * And adds a fk from the delegate to the main table if not already set
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function modifyTable()
    {
        $table = $this->getTable();
        $database = $table->getDatabase();
        $delegates = explode(',', $this->parameters['to']);
        foreach ($delegates as $delegate) {
            $delegate = $database->getTablePrefix() . trim($delegate);
            if (!$database->hasTable($delegate)) {
                throw new InvalidArgumentException(sprintf(
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
                        throw new InvalidArgumentException(sprintf(
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

    /**
     * @param \Propel\Generator\Model\Table $delegateTable
     * @param \Propel\Generator\Model\Table $mainTable
     *
     * @return void
     */
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

    /**
     * @param string $delegateTableName
     *
     * @return \Propel\Generator\Model\Table|null
     */
    protected function getDelegateTable($delegateTableName)
    {
        return $this->getTable()->getDatabase()->getTable($delegateTableName);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return string
     */
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
if (method_exists({$ARFQCN}::class, \$name)) {
    \$delegate = \$this->get$relationName();
    if (!\$delegate) {
        \$delegate = new $ARClassName();
        \$this->set$relationName(\$delegate);
    }

    return call_user_func_array(array(\$delegate, \$name), \$params);
}";
        }

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function objectFilter(&$script)
    {
        $p = new PhpParser($script, true);
        $text = $p->findMethod('toArray');
        $matches = [];
        preg_match('/(\$result = array\(([^;]+)\);)/U', $text, $matches);
        $values = rtrim($matches[2]) . "\n";
        $new_result = '';
        $indent = '        ';

        foreach ($this->delegates as $key => $value) {
            $delegateTable = $this->getDelegateTable($key);

            $tn = ($delegateTable->getSchema() ? $delegateTable->getSchema() . NameGeneratorInterface::STD_SEPARATOR_CHAR : '') . $delegateTable->getCommonName();
            $ns = $delegateTable->getNamespace() ? '\\' . $delegateTable->getNamespace() : '';
            $new_result .= "{$indent}\$keys_{$tn} = {$ns}\\Map\\{$delegateTable->getPhpName()}TableMap::getFieldNames(\$keyType);\n";
            $i = 0;
            foreach ($delegateTable->getColumns() as $column) {
                if (!$this->isColumnForeignKeyOrDuplicated($column)) {
                    $values .= "{$indent}    \$keys_{$tn}[{$i}] => \$this->get{$column->getPhpName()}(),\n";
                }
                $i++;
            }
        }

        $new_result .= "{$indent}\$result = array({$values}\n{$indent});";
        $text = str_replace($matches[1], ltrim($new_result), $text);
        $p->replaceMethod('toArray', $text);
        $script = $p->getCode();
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     *
     * @return bool
     */
    protected function isColumnForeignKeyOrDuplicated(Column $column)
    {
        $delegateTable = $column->getTable();
        $table = $this->getTable();
        $fks = [];

        if ($this->double_defined === null) {
            $this->double_defined = [];

            foreach ($this->delegates + [$table->getName() => 1] as $key => $value) {
                $delegateTable = $this->getDelegateTable($key);
                foreach ($delegateTable->getColumns() as $columnDelegated) {
                    if (isset($this->double_defined[$columnDelegated->getName()])) {
                        $this->double_defined[$columnDelegated->getName()]++;
                    } else {
                        $this->double_defined[$columnDelegated->getName()] = 1;
                    }
                }
            }
        }

        if (1 < $this->double_defined[$column->getName()]) {
            return true;
        }

        foreach ($delegateTable->getForeignKeysReferencingTable($table->getName()) as $fk) {
            /** @var \Propel\Generator\Model\ForeignKey $fk */
            $fks[] = $fk->getForeignColumnName();
        }

        foreach ($table->getForeignKeysReferencingTable($delegateTable->getName()) as $fk) {
            $fks[] = $fk->getForeignColumnName();
        }

        if (in_array($column->getName(), $fks) || $table->hasColumn($column->getName())) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
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

    /**
     * @param \Propel\Generator\Builder\Om\QueryBuilder $builder
     *
     * @return string
     */
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
