<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\SqlBuilder;

class PreparedStatementDto
{
    /**
     * @var string
     */
    private $sqlStatement;

    /**
     * @var array<mixed>
     */
    private $parameters;

    /**
     * @param string $sqlStatement
     * @param array $parameters
     */
    public function __construct(string $sqlStatement, array &$parameters = [])
    {
        $this->sqlStatement = $sqlStatement;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getSqlStatement(): string
    {
        return $this->sqlStatement;
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
