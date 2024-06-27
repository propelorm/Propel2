<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\SyncedTable;

use Propel\Runtime\Exception\PropelException;
use Throwable;

class SyncedTableException extends PropelException
{
    /**
     * Constructs the Exception.
     *
     * @param \Propel\Generator\Behavior\SyncedTable\SyncedTableBehaviorDeclaration $behavior
     * @param string $message The Exception message to throw.
     * @param int $code The Exception code.
     * @param \Throwable|null $previous The previous exception used for the exception chaining.
     */
    public function __construct(SyncedTableBehaviorDeclaration $behavior, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $messageHead = "Behavior '{$behavior->getName()}' on table '{$behavior->getTable()->getName()}': ";
        parent::__construct($messageHead . $message, $code, $previous);
    }
}
