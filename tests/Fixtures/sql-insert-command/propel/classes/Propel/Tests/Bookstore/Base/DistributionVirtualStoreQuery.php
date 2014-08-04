<?php

namespace Propel\Tests\Bookstore\Base;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Tests\Bookstore\Map\DistributionTableMap;

/**
 * Skeleton subclass for representing a query for one of the subclasses of the 'distribution' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class DistributionVirtualStoreQuery extends DistributionStoreQuery
{

    /**
     * Returns a new \Propel\Tests\Bookstore\DistributionVirtualStoreQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return \Propel\Tests\Bookstore\DistributionVirtualStoreQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof \Propel\Tests\Bookstore\DistributionVirtualStoreQuery) {
            return $criteria;
        }
        $query = new \Propel\Tests\Bookstore\DistributionVirtualStoreQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Filters the query to target only DistributionVirtualStore objects.
     */
    public function preSelect(ConnectionInterface $con)
    {
        $this->addUsingAlias(DistributionTableMap::COL_TYPE, DistributionTableMap::CLASSKEY_3838);
    }

    /**
     * Filters the query to target only DistributionVirtualStore objects.
     */
    public function preUpdate(&$values, ConnectionInterface $con, $forceIndividualSaves = false)
    {
        $this->addUsingAlias(DistributionTableMap::COL_TYPE, DistributionTableMap::CLASSKEY_3838);
    }

    /**
     * Filters the query to target only DistributionVirtualStore objects.
     */
    public function preDelete(ConnectionInterface $con)
    {
        $this->addUsingAlias(DistributionTableMap::COL_TYPE, DistributionTableMap::CLASSKEY_3838);
    }

    /**
     * Issue a DELETE query based on the current ModelCriteria deleting all rows in the table
     * Having the DistributionVirtualStore class.
     * This method is called by ModelCriteria::deleteAll() inside a transaction
     *
     * @param ConnectionInterface $con a connection object
     *
     * @return integer the number of deleted rows
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        // condition on class key is already added in preDelete()
        return parent::delete($con);
    }

} // DistributionVirtualStoreQuery
