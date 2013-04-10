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
use Propel\Runtime\Util\BasePeer;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\Criterion\SeveralModelCriterion;

/**
 */
class DocumentModelCriteria implements \IteratorAggregate
{

    private $dbName;
    private $originalDbName;
    private $modelName;
    private $modelTableMapName;
    private $modelPeerName;
    private $modelAlias;
    private $tableMap;
    private $formatter;
    protected $defaultFormatterClass = ModelCriteria::FORMAT_OBJECT;

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
        $this->modelPeerName     = constant($this->modelTableMapName . '::PEER_CLASS');
        $this->modelAlias        = $modelAlias;
        $this->tableMap          = Propel::getServiceContainer()->getDatabaseMap($this->getDbName())->getTableByPhpName($this->modelName);
    }


    public function setDbName($dbName = null)
    {
        $this->dbName = (null === $dbName ? Propel::getServiceContainer()->getDefaultDatasource() : $dbName);
    }

    public function getDbName()
    {
        return $this->dbName;
    }

    public function getFormatter()
    {
        if (null === $this->formatter) {
            $formatterClass = $this->defaultFormatterClass;
            $this->formatter = new $formatterClass();
        }

        return $this->formatter;
    }

    public function find($query, $con)
    {

        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }

        $dbName = $this->getDbName();

        $row = $con->getCollection($dbName::TABLE_NAME)->findOne($query);

        //todo, use a FormatFactory that retursn the correct (non-pdo) formatter.
        return $this->getFormatter()->init($this)->format($stmt);

    }

    /**
     * Execute the query with a find(), and return a Traversable object.
     *
     * The return value depends on the query formatter. By default, this returns an ArrayIterator
     * constructed on a Propel\Runtime\Collection\PropelCollection.
     * Compulsory for implementation of \IteratorAggregate.
     *
     * @return Traversable
     */
    public function getIterator()
    {
        $res = $this->find(null); // use the default connection
        if ($res instanceof \IteratorAggregate) {
            return $res->getIterator();
        }
        if ($res instanceof \Traversable) {
            return $res;
        }
        if (is_array($res)) {
            return new \ArrayIterator($res);
        }
        throw new LogicException('The current formatter doesn\'t return an iterable result');
    }
}