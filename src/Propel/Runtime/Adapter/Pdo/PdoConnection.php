<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\InvalidArgumentException;

use \PDO;

/**
 * PDO extension that implements ConnectionInterface and builds statements implementting StatementInterface.
 */
class PdoConnection extends PDO implements ConnectionInterface
{
    /**
     * Creates a PDO instance representing a connection to a database.
     */
    public function __construct($dsn, $user = null, $password = null, array $options = null)
    {
        parent::__construct($dsn, $user, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\Propel\Runtime\Adapter\Pdo\PdoStatement', array()));
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Sets a connection attribute.
     *
     * This is overridden here to allow names corresponding to PDO constant names.
     *
     * @param     integer  $attribute  The attribute to set (e.g. 'PDO::ATTR_CASE', or more simply 'ATTR_CASE').
     * @param     mixed    $value  The attribute value.
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute) && false === strpos($attribute, '::')) {
            $attribute = '\PDO::' . $attribute;
            if (!defined($attribute)) {
                throw new InvalidArgumentException(sprintf('Invalid PDO option/attribute name specified: "%s"', $attribute));
            }
            $attribute = constant($attribute);
        }
        parent::setAttribute($attribute, $value);
    }
}
