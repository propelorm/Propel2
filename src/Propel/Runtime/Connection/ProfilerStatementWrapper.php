<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use PDO;

/**
 * Statement class with profiling abilities.
 */
class ProfilerStatementWrapper extends StatementWrapper
{
    /**
     * Binds a PHP variable to a corresponding named or question mark placeholder in the SQL statement
     * that was use to prepare the statement. Unlike PDOStatement::bindValue(), the variable is bound
     * as a reference and will only be evaluated at the time that PDOStatement::execute() is called.
     * Returns a boolean value indicating success.
     *
     * @param int $parameter Parameter identifier (for determining what to replace in the query).
     * @param mixed $variable The value to bind to the parameter.
     * @param int $dataType Explicit data type for the parameter using the PDO::PARAM_* constants. Defaults to PDO::PARAM_STR.
     * @param int|null $length Length of the data type. To indicate that a parameter is an OUT parameter from a stored procedure, you must explicitly set the length.
     * @param mixed $driverOptions
     *
     * @return bool
     */
    public function bindParam($parameter, &$variable, $dataType = PDO::PARAM_STR, $length = 0, $driverOptions = null)
    {
        $this->connection->getProfiler()->start();

        return parent::bindParam($parameter, $variable, $dataType, $length, $driverOptions);
    }

    /**
     * Binds a value to a corresponding named or question mark placeholder in the SQL statement
     * that was use to prepare the statement. Returns a boolean value indicating success.
     *
     * @param int $parameter Parameter identifier (for determining what to replace in the query).
     * @param mixed $value The value to bind to the parameter.
     * @param int $dataType Explicit data type for the parameter using the PDO::PARAM_* constants. Defaults to PDO::PARAM_STR.
     *
     * @return bool
     */
    public function bindValue($parameter, $value, $dataType = PDO::PARAM_STR)
    {
        $this->connection->getProfiler()->start();

        return parent::bindValue($parameter, $value, $dataType);
    }

    /**
     * Executes a prepared statement. Returns a boolean value indicating success.
     * Overridden for query counting and logging.
     *
     * @param array|null $parameters
     *
     * @return bool
     */
    public function execute($parameters = null)
    {
        $this->connection->getProfiler()->start();

        return parent::execute($parameters);
    }
}
