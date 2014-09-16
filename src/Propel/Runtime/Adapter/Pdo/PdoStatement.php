<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\Connection\StatementInterface;
use PDOStatement as BasePdoStatement;

/**
 * PDO statement that provides the basic enhancements that are required by Propel.
 */
class PdoStatement extends BasePdoStatement implements StatementInterface
{
    protected function __construct()
    {
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     */
    public function bindParam(
        $parameter,
        &$variable,
        $data_type = \PDO::PARAM_STR,
        $length = null,
        $driver_options = null
    ) {
        return parent::bindParam(
            $parameter,
            $variable,
            $data_type,
            $length,
            $driver_options
        );
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     */
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {
        return parent::bindValue($parameter, $value, $data_type);
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     */
    public function execute($parameters = null)
    {
        return parent::execute($parameters);
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     */
    public function fetch($fetch_style = null, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        return parent::fetch($fetch_style, $cursor_orientation, $cursor_offset);
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = array())
    {
        switch (func_num_args()) {
        case 0:
            return parent::fetchAll();
        case 1:
            return parent::fetchAll($fetch_style);
        case 2:
            return parent::fetchAll($fetch_style, $fetch_argument);
        case 3:
            return parent::fetchAll($fetch_style, $fetch_argument, $ctor_args);
        }
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     */
    public function fetchObject($class_name = "stdClass", $ctor_args = null)
    {
        return parent::fetchObject($class_name, $ctor_args);
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     */
    public function fetchColumn($column_number = 0)
    {
        return parent::fetchColumn($column_number);
    }

}
