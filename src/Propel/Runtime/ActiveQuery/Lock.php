<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Propel\Runtime\ActiveQuery;

/**
 * Class represents a query lock
 *
 * @author Tomasz Wójcik <tomasz.prgtw.wojcik@gmail.com>
 */
class Lock
{
    /**
     * @var string
     */
    public const SHARED = 'SHARED';

    /**
     * @var string
     */
    public const EXCLUSIVE = 'EXCLUSIVE';

    /**
     * Lock type, either shared or exclusive
     *
     * @see self::SHARED
     * @see self::EXCLUSIVE
     *
     * @var string
     */
    protected $type;

    /**
     * Table names to lock
     *
     * @var array<string>
     */
    protected $tableNames;

    /**
     * Whether to issue a non-blocking lock
     *
     * @var bool
     */
    protected $noWait;

    /**
     * @param string $type Lock type
     * @param array<string> $tableNames Table names to lock
     * @param bool $noWait Whether to issue a non-blocking lock
     */
    public function __construct(string $type, array $tableNames = [], bool $noWait = false)
    {
        $this->type = $type;
        $this->tableNames = $tableNames;
        $this->noWait = $noWait;
    }

    /**
     * Lock type
     *
     * @see self::SHARED
     * @see self::EXCLUSIVE
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns table names to lock
     *
     * @return array<string>
     */
    public function getTableNames(): array
    {
        return $this->tableNames;
    }

    /**
     * Whether to issue a non-blocking lock
     *
     * @return bool
     */
    public function isNoWait(): bool
    {
        return $this->noWait;
    }

    /**
     * Checks whether a lock equals another lock object
     *
     * @param \Propel\Runtime\ActiveQuery\Lock|mixed $lock
     *
     * @return bool
     */
    public function equals($lock): bool
    {
        if (!($lock instanceof self)) {
            return false;
        }

        $aTableNames = $this->getTableNames();
        $bTableNames = $lock->getTableNames();

        return $this->getType() === $lock->getType()
            && $this->isNoWait() === $lock->isNoWait()
            && $aTableNames === array_intersect($aTableNames, $bTableNames)
            && $bTableNames === array_intersect($bTableNames, $aTableNames);
    }
}
