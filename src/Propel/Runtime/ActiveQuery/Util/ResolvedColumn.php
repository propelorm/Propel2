<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Propel\Runtime\ActiveQuery\Util;

use Propel\Runtime\Map\ColumnMap;

class ResolvedColumn
{
 /**
  * @var \Propel\Runtime\Map\ColumnMap|null
  */
    protected $columnMap;

    /**
     * @var string|null
     */
    protected $localColumnName;

    /**
     * @var string|null
     */
    protected $tableAlias;

    /**
     * @param string $localColumnName
     * @param \Propel\Runtime\Map\ColumnMap|null $columnMap
     * @param string|null $tableAlias
     */
    public function __construct(string $localColumnName, ?ColumnMap $columnMap = null, ?string $tableAlias = null)
    {
        $this->columnMap = $columnMap;
        $this->localColumnName = $localColumnName;
        $this->tableAlias = $tableAlias;
    }

    /**
     * Creates an empty ResolvedColumn.
     *
     * Used when a column cannot be found but no error message is expected.
     *
     * @return \Propel\Runtime\ActiveQuery\Util\ResolvedColumn
     */
    public static function getEmptyResolvedColumn()
    {
        $result = new self('');
        $result->localColumnName = null;

        return $result;
    }

    /**
     * @return string|null
     */
    public function getLocalColumnName(): ?string
    {
        return $this->localColumnName;
    }

    /**
     * @return \Propel\Runtime\Map\ColumnMap|null
     */
    public function getColumnMap(): ?ColumnMap
    {
        return $this->columnMap;
    }

    /**
     * @return string|null
     */
    public function getTableAlias(): ?string
    {
        return $this->tableAlias;
    }

    /**
     * @return bool
     */
    public function isFromLocalTable(): bool
    {
        return $this->columnMap !== null;
    }
}
