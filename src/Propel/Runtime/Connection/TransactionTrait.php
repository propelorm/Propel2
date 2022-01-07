<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use Throwable;

/**
 * Transaction helper trait
 */
trait TransactionTrait
{
    /**
     * Executes the given callable within a transaction.
     * This helper method takes care to commit or rollback the transaction.
     *
     * In case you want the transaction to rollback just throw an Throwable of any type.
     *
     * @param callable $callable A callable to be wrapped in a transaction
     *
     * @throws \Throwable Re-throws a possible <code>Throwable</code> triggered by the callable.
     *
     * @return mixed Returns the result of the callable.
     */
    public function transaction(callable $callable)
    {
        $this->beginTransaction();

        try {
            $result = $callable();

            $this->commit();

            return $result;
        } catch (Throwable $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * @return bool
     */
    abstract public function beginTransaction(): bool;

    /**
     * @return bool
     */
    abstract public function commit(): bool;

    /**
     * @return bool
     */
    abstract public function rollBack(): bool;
}
