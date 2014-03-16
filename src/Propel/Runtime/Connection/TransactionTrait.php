<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Connection;

/**
 * Transaction helper trait
 */
trait TransactionTrait
{
    /**
     * Executes the given callable within a transaction.
     * This helper method takes care to commit or rollback the transaction.
     *
     * In case you want the transaction to rollback just throw an Exception of any type.
     *
     * @param callable $callable A callable to be wrapped in a transaction
     *
     * @return mixed Returns the result of the callable.
     *
     * @throws \Exception Re-throws a possible <code>Exception</code> triggered by the callable.
     */
    public function transaction(callable $callable)
    {
        $this->beginTransaction();

        try {
            $result = call_user_func($callable);

            $this->commit();

            return $result;
        } catch (\Exception $e) {
            $this->rollBack();

            throw $e;
        }
    }

    abstract public function beginTransaction();

    abstract public function commit();

    abstract public function rollBack();
}
