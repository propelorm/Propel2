<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\Propel;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Connection\MongoConnection;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\Criterion\SeveralModelCriterion;
use Propel\Runtime\Formatter\MongoDataFetcher;
use Propel\Runtime\Formatter\ArrayDataFetcher;

/**
 */
class DocumentModelCriteria extends BaseModelCriteria
{

    /**
     * Initializes internal state of \Propel\Tests\Bookstore\Base\BookQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = null, $modelName = null, $modelAlias = null)
    {
        $this->setDbName($dbName);
        $this->originalDbName = $dbName;

        if (0 === strpos($modelName, '\\')) {
            $this->modelName = substr($modelName, 1);
        } else {
            $this->modelName = $modelName;
        }

        $this->modelTableMapName = constant($this->modelName . '::TABLE_MAP');
        $this->modelAlias        = $modelAlias;
        $this->tableMap          = Propel::getServiceContainer()->getDatabaseMap($this->getDbName())->getTableByPhpName($this->modelName);
    }

    public function find($query = null, MongoConnection $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $cursor = $con
            ->getCollection(constant($this->modelTableMapName.'::TABLE_NAME'))
            ->find($query ?: array());

        return $this
            ->getFormatter()
            ->init($this, new $con->getDataFetcher($cursor))
            ->format();
    }

    public function findOne($query = null, MongoConnection $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $document = $con
            ->getCollection(constant($this->modelTableMapName.'::TABLE_NAME'))
            ->findOne($query ?: array());

        if ($document) {
            return $this
                ->getFormatter()
                ->init($this, $con->getSingleDataFetcher(array($document)))
                ->formatOne();
        }
    }

}