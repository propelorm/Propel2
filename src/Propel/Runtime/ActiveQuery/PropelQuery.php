<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
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
     * @param $queryClassAndAlias
     * @return ModelCriteria
     * @throws \Propel\Runtime\Exception\ClassNotFoundException
     */
    public static function from($queryClassAndAlias)
    {
        list($class, $alias) = ModelCriteria::getClassAndAlias($queryClassAndAlias);
        $queryClass = $class . 'Query';
        if (!class_exists($queryClass)) {
            throw new ClassNotFoundException('Cannot find a query class for ' . $class);
        }
        /** @var ModelCriteria $query */
        $query = new $queryClass();
        if (null !== $alias) {
            $query->setModelAlias($alias);
        }

        return $query;
    }
}
