<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\Exception\ClassNotFoundException;

/**
 * Factory for model queries
 *
 * @author François Zaninotto
 */
class PropelQuery
{
    /**
     * @param string $queryClassAndAlias
     *
     * @throws \Propel\Runtime\Exception\ClassNotFoundException
     *
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    public static function from(string $queryClassAndAlias): ModelCriteria
    {
        [$class, $alias] = ModelCriteria::getClassAndAlias($queryClassAndAlias);
        $queryClass = $class . 'Query';
        if (!class_exists($queryClass)) {
            throw new ClassNotFoundException('Cannot find a query class for ' . $class);
        }
        /** @var \Propel\Runtime\ActiveQuery\ModelCriteria $query */
        $query = new $queryClass();
        if ($alias !== null) {
            $query->setModelAlias($alias);
        }

        return $query;
    }
}
