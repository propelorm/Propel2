<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\Criterion\CriterionFactory;
use Propel\Runtime\ActiveQuery\Join as ActiveQueryJoin;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Exception\LogicException;

/**
 * Data object to describe a join between two tables, for example
 * <pre>
 * table_a LEFT JOIN table_b ON table_a.id = table_b.a_id
 * </pre>
 *
 * @author Francois Zaninotto (Propel)
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Kaspars Jaudzems <kaspars.jaudzems@inbox.lv> (Propel)
 * @author Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Eric Dobbs <eric@dobbse.net> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Sam Joseph <sam@neurogrid.com> (Torque)
 */
class Join
{
    // default comparison type
    /**
     * @var string
     */
    public const EQUAL = '=';

    /**
     * @var string
     */
    public const INNER_JOIN = 'INNER JOIN';

    /**
     * The left parts of the join condition
     *
     * @var list<string|null>
     */
    protected $left = [];

    /**
     * @var array
     */
    protected $leftValues = [];

    /**
     * @var array
     */
    protected $rightValues = [];

    /**
     * The right parts of the join condition
     *
     * @var list<string|null>
     */
    protected $right = [];

    /**
     * The comparison operators for each pair of columns in the join condition
     *
     * @var array<int, string>
     */
    protected $operators = [];

    /**
     * The type of the join (LEFT JOIN, ...)
     *
     * @var string|null
     */
    protected $joinType;

    /**
     * The number of conditions in the join
     *
     * @var int
     */
    protected $count = 0;

    /**
     * @var \Propel\Runtime\Adapter\AdapterInterface|null
     */
    protected $db;

    /**
     * @var string|null
     */
    protected $leftTableName;

    /**
     * @var string|null
     */
    protected $rightTableName;

    /**
     * @var string|null
     */
    protected $leftTableAlias;

    /**
     * @var string|null
     */
    protected $rightTableAlias;

    /**
     * @var \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion|null
     */
    protected $joinCondition;

    /**
     * @var bool
     */
    protected $identifierQuoting = false;

    /**
     * Constructor
     * Use it preferably with no arguments, and then use addCondition() and setJoinType()
     * Syntax with arguments used mainly for backwards compatibility
     *
     * @param array<string>|string|null $leftColumn The left column of the join condition
     *                            (may contain an alias name)
     * @param array<string>|string|null $rightColumn The right column of the join condition
     *                            (may contain an alias name)
     * @param string|null $joinType The type of the join. Valid join types are null (implicit join),
     *                            Criteria::LEFT_JOIN, Criteria::RIGHT_JOIN, and Criteria::INNER_JOIN
     */
    public function __construct($leftColumn = null, $rightColumn = null, ?string $joinType = null)
    {
        if ($leftColumn !== null && $rightColumn !== null) {
            if (is_array($leftColumn) && is_array($rightColumn)) {
                // join with multiple conditions
                $this->addConditions($leftColumn, $rightColumn);
            } else {
                // simple join
                if (is_string($leftColumn) && is_string($rightColumn)) {
                    $this->addCondition($leftColumn, $rightColumn);
                }
            }
        }

        if ($joinType !== null) {
            $this->setJoinType($joinType);
        }
    }

    /**
     * Join condition definition.
     * Warning: doesn't support table aliases. Use the explicit methods to use aliases.
     *
     * @param string $left The left column of the join condition
     *                         (may contain an alias name)
     * @param string $right The right column of the join condition
     *                         (may contain an alias name)
     * @param string $operator The comparison operator of the join condition, default Join::EQUAL
     *
     * @return void
     */
    public function addCondition(string $left, string $right, string $operator = self::EQUAL): void
    {
        if (strrpos($left, '.')) {
            [$this->leftTableName, $this->left[]] = explode('.', $left);
        } else {
            $this->left[] = $left;
        }

        if (strrpos($right, '.')) {
            [$this->rightTableName, $this->right[]] = explode('.', $right);
        } else {
            $this->right[] = $right;
        }

        $this->leftValues[] = null;
        $this->rightValues[] = null;
        $this->operators[] = $operator;
        $this->count++;
    }

    /**
     * Join condition definition, for several conditions
     *
     * @param array<string> $lefts The left columns of the join condition
     * @param array<string> $rights The right columns of the join condition
     * @param array<string> $operators The comparison operators of the join condition, default Join::EQUAL
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return void
     */
    public function addConditions(array $lefts, array $rights, array $operators = []): void
    {
        if (count($lefts) != count($rights)) {
            throw new LogicException("Unable to create join because the left column count isn't equal to the right column count");
        }

        foreach ($lefts as $key => $left) {
            $this->addCondition($left, $rights[$key], $operators[$key] ?? self::EQUAL);
        }
    }

    /**
     * Join condition definition.
     *
     * @example
     * <code>
     * $join = new Join();
     * $join->setJoinType(Criteria::LEFT_JOIN);
     * $join->addExplicitCondition('book', 'AUTHOR_ID', null, 'author', 'ID', 'a', Join::EQUAL);
     * echo $join->getClause();
     * // LEFT JOIN author a ON (book.AUTHOR_ID=a.ID)
     * </code>
     *
     * @param string $leftTableName
     * @param string $leftColumnName
     * @param string|null $leftTableAlias
     * @param string|null $rightTableName
     * @param string|null $rightColumnName
     * @param string|null $rightTableAlias
     * @param string $operator The comparison operator of the join condition, default Join::EQUAL
     *
     * @return void
     */
    public function addExplicitCondition(
        string $leftTableName,
        string $leftColumnName,
        ?string $leftTableAlias = null,
        ?string $rightTableName = null,
        ?string $rightColumnName = null,
        ?string $rightTableAlias = null,
        string $operator = self::EQUAL
    ): void {
        $this->leftTableName = $leftTableName;
        $this->leftTableAlias = $leftTableAlias;
        $this->rightTableName = $rightTableName;
        $this->rightTableAlias = $rightTableAlias;
        $this->left[] = $leftColumnName;
        $this->leftValues[] = null;
        $this->rightValues[] = null;
        $this->right[] = $rightColumnName;
        $this->operators[] = $operator;
        $this->count++;
    }

    /**
     * @param string $leftTableName
     * @param string $leftColumnName
     * @param string|null $leftTableAlias
     * @param mixed $leftColumnValue
     * @param string $operator
     *
     * @return void
     */
    public function addLocalValueCondition(
        string $leftTableName,
        string $leftColumnName,
        ?string $leftTableAlias,
        $leftColumnValue,
        string $operator = self::EQUAL
    ): void {
        $this->leftTableName = $leftTableName;
        $this->leftTableAlias = $leftTableAlias;
        $this->left[] = $leftColumnName;
        $this->leftValues[] = $leftColumnValue;
        $this->rightValues[] = null;
        $this->right[] = null;
        $this->operators[] = $operator;
        $this->count++;
    }

    /**
     * @param string $rightTableName
     * @param string $rightColumnName
     * @param string|null $rightTableAlias
     * @param mixed $rightColumnValue
     * @param string $operator
     *
     * @return void
     */
    public function addForeignValueCondition(
        string $rightTableName,
        string $rightColumnName,
        ?string $rightTableAlias,
        $rightColumnValue,
        string $operator = self::EQUAL
    ): void {
        $this->rightTableName = $rightTableName;
        $this->rightTableAlias = $rightTableAlias;
        $this->right[] = $rightColumnName;
        $this->rightValues[] = $rightColumnValue;
        $this->leftValues[] = null;
        $this->left[] = null;
        $this->operators[] = $operator;
        $this->count++;
    }

    /**
     * Retrieve the number of conditions in the join
     *
     * @return int The number of conditions in the join
     */
    public function countConditions(): int
    {
        return $this->count;
    }

    /**
     * Return an array of the join conditions
     *
     * @return array An array of arrays representing (left, comparison, right) for each condition
     */
    public function getConditions(): array
    {
        $conditions = [];
        for ($i = 0; $i < $this->count; $i++) {
            $conditions[] = [
                'left' => $this->getLeftColumn($i),
                'operator' => $this->getOperator($i),
                'right' => $this->getRightColumn($i),
            ];
        }

        return $conditions;
    }

    /**
     * @param string $operator the comparison operator for the join condition
     *
     * @return void
     */
    public function addOperator(string $operator): void
    {
        $this->operators[] = $operator;
    }

    /**
     * @param int $index
     *
     * @return string the comparison operator for the join condition
     */
    public function getOperator(int $index = 0): string
    {
        return $this->operators[$index];
    }

    /**
     * @return array<int, string>
     */
    public function getOperators(): array
    {
        return $this->operators;
    }

    /**
     * Set the join type
     *
     * @param string|null $joinType The type of the join. Valid join types are
     *                         null (adding the join condition to the where clause),
     *                         Criteria::LEFT_JOIN(), Criteria::RIGHT_JOIN(), and Criteria::INNER_JOIN()
     *
     * @return void
     */
    public function setJoinType(?string $joinType): void
    {
        $this->joinType = $joinType;
    }

    /**
     * Get the join type
     *
     * @return string The type of the join, i.e. Criteria::LEFT_JOIN(), ...,
     *                or null for adding the join condition to the where Clause
     */
    public function getJoinType(): string
    {
        return $this->joinType ?? self::INNER_JOIN;
    }

    /**
     * Add a left column name to the join condition
     *
     * @example
     * <code>
     * $join->setLeftTableName('book');
     * $join->addLeftColumnName('AUTHOR_ID');
     * </code>
     *
     * @param string $left The name of the left column to add
     *
     * @return void
     */
    public function addLeftColumnName(string $left): void
    {
        $this->left[] = $left;
    }

    /**
     * Adds a value for a leftColumn.
     *
     * @param mixed $value an actual value
     *
     * @return void
     */
    public function addLeftValue($value): void
    {
        $this->leftValues = $value;
    }

    /**
     * Get the fully qualified name of the left column of the join condition
     *
     * @example
     * <code>
     * $join->addCondition('book.AUTHOR_ID', 'author.ID');
     * echo $join->getLeftColumn(); // 'book.AUTHOR_ID'
     * </code>
     *
     * @param int $index The number of the condition to use
     *
     * @return string
     */
    public function getLeftColumn(int $index = 0): string
    {
        $tableName = $this->getLeftTableAliasOrName();

        return $tableName ? $tableName . '.' . $this->left[$index] : $this->left[$index];
    }

    /**
     * Get the left column name of the join condition
     *
     * @example
     * <code>
     * $join->addCondition('book.AUTHOR_ID', 'author.ID');
     * echo $join->getLeftColumnName(); // 'AUTHOR_ID'
     * </code>
     *
     * @param int $index The number of the condition to use
     *
     * @return string
     */
    public function getLeftColumnName(int $index = 0): string
    {
        return $this->left[$index];
    }

    /**
     * Get the list of all the names of left columns of the join condition
     *
     * @return array
     */
    public function getLeftColumns(): array
    {
        $columns = [];
        foreach ($this->left as $index => $column) {
            $columns[] = $this->getLeftColumn($index);
        }

        return $columns;
    }

    /**
     * @param string $leftTableName
     *
     * @return $this
     */
    public function setLeftTableName(string $leftTableName)
    {
        $this->leftTableName = $leftTableName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLeftTableName(): ?string
    {
        return $this->leftTableName;
    }

    /**
     * @param string $leftTableAlias
     *
     * @return $this
     */
    public function setLeftTableAlias(string $leftTableAlias)
    {
        $this->leftTableAlias = $leftTableAlias;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLeftTableAlias(): ?string
    {
        return $this->leftTableAlias;
    }

    /**
     * @return bool
     */
    public function hasLeftTableAlias(): bool
    {
        return $this->leftTableAlias !== null;
    }

    /**
     * @return string|null
     */
    public function getLeftTableAliasOrName(): ?string
    {
        return $this->leftTableAlias ?: $this->leftTableName;
    }

    /**
     * @return string|null
     */
    public function getLeftTableWithAlias(): ?string
    {
        return $this->leftTableAlias ? $this->leftTableName . ' ' . $this->leftTableAlias : $this->leftTableName;
    }

    /**
     * Add a right column name to the join condition
     *
     * @example
     * <code>
     * $join->setRightTableName('author');
     * $join->addRightColumnName('ID');
     * </code>
     *
     * @param string $right The name of the right column to add
     *
     * @return void
     */
    public function addRightColumnName(string $right): void
    {
        $this->right[] = $right;
    }

    /**
     * Get the fully qualified name of the right column of the join condition
     *
     * @example
     * <code>
     * $join->addCondition('book.AUTHOR_ID', 'author.ID');
     * echo $join->getLeftColumn(); // 'author.ID'
     * </code>
     *
     * @param int $index The number of the condition to use
     *
     * @return string
     */
    public function getRightColumn(int $index = 0): string
    {
        $tableName = $this->getRightTableAliasOrName();

        return $tableName ? $tableName . '.' . $this->right[$index] : $this->right[$index];
    }

    /**
     * Get the right column name of the join condition
     *
     * @example
     * <code>
     * $join->addCondition('book.AUTHOR_ID', 'author.ID');
     * echo $join->getLeftColumn(); // 'ID'
     * </code>
     *
     * @param int $index The number of the condition to use
     *
     * @return string
     */
    public function getRightColumnName(int $index = 0): string
    {
        return $this->right[$index];
    }

    /**
     * @return array All right columns of the join condition
     */
    public function getRightColumns(): array
    {
        $columns = [];
        foreach ($this->right as $index => $column) {
            $columns[] = $this->getRightColumn($index);
        }

        return $columns;
    }

    /**
     * @param string $rightTableName
     *
     * @return $this
     */
    public function setRightTableName(string $rightTableName)
    {
        $this->rightTableName = $rightTableName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRightTableName(): ?string
    {
        return $this->rightTableName;
    }

    /**
     * @param string $rightTableAlias
     *
     * @return $this
     */
    public function setRightTableAlias(string $rightTableAlias)
    {
        $this->rightTableAlias = $rightTableAlias;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRightTableAlias(): ?string
    {
        return $this->rightTableAlias;
    }

    /**
     * @return bool
     */
    public function hasRightTableAlias(): bool
    {
        return $this->rightTableAlias !== null;
    }

    /**
     * @return string|null
     */
    public function getRightTableAliasOrName(): ?string
    {
        return $this->rightTableAlias ?: $this->rightTableName;
    }

    /**
     * @return string|null
     */
    public function getRightTableWithAlias(): ?string
    {
        return $this->rightTableAlias ? $this->rightTableName . ' ' . $this->rightTableAlias : $this->rightTableName;
    }

    /**
     * Get the adapter.
     *
     * The AdapterInterface which might be used to get db specific
     * variations of sql.
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface|null value of db.
     */
    public function getAdapter(): ?AdapterInterface
    {
        return $this->db;
    }

    /**
     * Set the adapter.
     *
     * The AdapterInterface might be used to get db specific variations of sql.
     *
     * @param \Propel\Runtime\Adapter\AdapterInterface $db Value to assign to db.
     *
     * @return void
     */
    public function setAdapter(AdapterInterface $db): void
    {
        $this->db = $db;
    }

    /**
     * Set a custom join condition
     *
     * @param \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion $joinCondition a Join condition
     *
     * @return void
     */
    public function setJoinCondition(AbstractCriterion $joinCondition): void
    {
        $this->joinCondition = $joinCondition;
    }

    /**
     * Get the custom join condition, if previously set
     *
     * @return \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion|null
     */
    public function getJoinCondition(): ?AbstractCriterion
    {
        return $this->joinCondition;
    }

    /**
     * Get the custom join condition, if previously set
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion
     */
    public function getJoinConditionOrFail(): AbstractCriterion
    {
        $joinCondition = $this->getJoinCondition();

        if ($joinCondition === null) {
            throw new LogicException('Join condition is not defined.');
        }

        return $joinCondition;
    }

    /**
     * Set the custom join condition Criterion based on the conditions of this join
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $c A Criteria object to get Criterions from
     *
     * @return void
     */
    public function buildJoinCondition(Criteria $c): void
    {
        /** @var \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion|null $joinCondition */
        $joinCondition = null;
        for ($i = 0; $i < $this->count; $i++) {
            if ($this->leftValues[$i]) {
                $criterion = CriterionFactory::build(
                    $c,
                    $this->getLeftColumn($i),
                    self::EQUAL,
                    $this->leftValues[$i],
                );
            } elseif ($this->rightValues[$i]) {
                $criterion = CriterionFactory::build(
                    $c,
                    $this->getRightColumn($i),
                    self::EQUAL,
                    $this->rightValues[$i],
                );
            } else {
                $criterion = CriterionFactory::build(
                    $c,
                    $this->getLeftColumn($i),
                    Criteria::CUSTOM,
                    $this->getLeftColumn($i) . $this->getOperator($i) . $this->getRightColumn($i),
                );
            }
            if ($joinCondition === null) {
                $joinCondition = $criterion;
            } else {
                $joinCondition = $joinCondition->addAnd($criterion);
            }
        }

        $this->joinCondition = $joinCondition;
    }

    /**
     * Get the join clause for this Join.
     * If the join condition needs binding, uses the passed params array.
     *
     * @example
     * <code>
     * $join = new Join();
     * $join->addExplicitCondition('book', 'AUTHOR_ID', null, 'author', 'ID');
     * $params = [];
     * echo $j->getClause($params);
     * // 'LEFT JOIN author ON (book.AUTHOR_ID=author.ID)'
     * </code>
     *
     * @param array $params
     *
     * @return string SQL join clause with join condition
     */
    public function getClause(array &$params): string
    {
        if ($this->joinCondition === null) {
            $conditions = [];
            for ($i = 0; $i < $this->count; $i++) {
                if ($this->leftValues[$i]) {
                    $conditions[] = $this->getLeftColumn($i) . $this->getOperator($i) . var_export($this->leftValues[$i], true);
                } elseif ($this->rightValues[$i]) {
                        $conditions[] = $this->getRightColumn($i) . $this->getOperator($i) . var_export($this->rightValues[$i], true);
                } else {
                    $conditions[] = $this->getLeftColumn($i) . $this->getOperator($i) . $this->getRightColumn($i);
                }
            }
            $joinCondition = sprintf('(%s)', implode(' AND ', $conditions));
        } else {
            $joinCondition = '';
            $this->getJoinCondition()->appendPsTo($joinCondition, $params);
        }

        $rightTableName = $this->getRightTableWithAlias();

        if ($this->isIdentifierQuotingEnabled()) {
            $rightTableName = $this->getAdapter()->quoteIdentifierTable($rightTableName);
        }

        return sprintf(
            '%s %s ON %s',
            $this->getJoinType(),
            $rightTableName,
            $joinCondition,
        );
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Join $join
     *
     * @return bool
     */
    public function equals(ActiveQueryJoin $join): bool
    {
        $parametersOfThisClauses = [];
        $parametersOfJoinClauses = [];

        return $this->getJoinType() === $join->getJoinType()
            && $this->getConditions() == $join->getConditions()
            && $this->getClause($parametersOfThisClauses) == $join->getClause($parametersOfJoinClauses);
    }

    /**
     * Returns a string representation of the class,
     *
     * @return string A string representation of the object
     */
    public function toString(): string
    {
        $params = [];

        return $this->getClause($params);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return bool
     */
    public function isIdentifierQuotingEnabled(): bool
    {
        return $this->identifierQuoting;
    }

    /**
     * @param bool $identifierQuoting
     *
     * @return void
     */
    public function setIdentifierQuoting(bool $identifierQuoting): void
    {
        $this->identifierQuoting = $identifierQuoting;
    }
}
