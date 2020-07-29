<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Util;

use Countable;
use IteratorAggregate;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;

/**
 * Implements a pager based on a ModelCriteria
 * The code from this class heavily borrows from symfony's sfPager class
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author François Zaninotto
 */
class PropelModelPager implements IteratorAggregate, Countable
{
    /**
     * @var \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    protected $query;

    /**
     * @var int current page
     */
    protected $page;

    /**
     * @var int number of item per page
     */
    protected $maxPerPage;

    /**
     * @var int index of the last page
     */
    protected $lastPage;

    /**
     * @var int number of item the query return without pagination
     */
    protected $nbResults;

    /**
     * @var int
     */
    protected $currentMaxLink;

    /**
     * @var int
     */
    protected $maxRecordLimit;

    /**
     * @var \Propel\Runtime\Collection\Collection|array|mixed
     */
    protected $results;

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $con;

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $query
     * @param int $maxPerPage
     */
    public function __construct(ModelCriteria $query, $maxPerPage = 10)
    {
        $this->setQuery($query);
        $this->setMaxPerPage($maxPerPage);
        $this->setPage(1);
        $this->setLastPage(1);
        $this->setMaxRecordLimit(0);
        $this->setNbResults(0);

        $this->currentMaxLink = 1;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $query
     *
     * @return void
     */
    public function setQuery(ModelCriteria $query)
    {
        $this->query = $query;
    }

    /**
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return void
     */
    public function init(?ConnectionInterface $con = null)
    {
        $this->con = $con;
        $maxRecordLimit = $this->getMaxRecordLimit();
        $hasMaxRecordLimit = (bool)$maxRecordLimit;

        $qForCount = clone $this->getQuery();
        $count = $qForCount
            ->offset(0)
            ->limit(-1)
            ->count($this->con);

        $this->setNbResults($hasMaxRecordLimit ? min($count, $maxRecordLimit) : $count);

        $q = $this->getQuery()
            ->offset(0)
            ->limit(-1);

        if ($this->getPage() === 0 || $this->getMaxPerPage() === 0) {
            $this->setLastPage(0);
        } else {
            $this->setLastPage((int)ceil($this->getNbResults() / $this->getMaxPerPage()));

            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
            $q->offset($offset);

            if ($hasMaxRecordLimit) {
                $maxRecordLimit = $maxRecordLimit - $offset;
                if ($maxRecordLimit > $this->getMaxPerPage()) {
                    $q->limit($this->getMaxPerPage());
                } else {
                    $q->limit($maxRecordLimit);
                }
            } else {
                $q->limit($this->getMaxPerPage());
            }
        }
    }

    /**
     * Get the collection of results in the page
     *
     * @return \Propel\Runtime\Collection\Collection A collection of results
     */
    public function getResults()
    {
        if ($this->results === null) {
            $queryKey = method_exists($this->getQuery(), 'getQueryKey') ? $this->getQuery()->getQueryKey() : null;
            if ($queryKey) {
                $newQueryKey = sprintf('%s offset %s limit %s', $queryKey, $this->getQuery()->getOffset(), $this->getQuery()->getLimit());
                $this->getQuery()->setQueryKey($newQueryKey);
            }

            $this->results = $this->getQuery()
                ->find($this->con);
        }

        return $this->results;
    }

    /**
     * @return int
     */
    public function getCurrentMaxLink()
    {
        return $this->currentMaxLink;
    }

    /**
     * @return int
     */
    public function getMaxRecordLimit()
    {
        return $this->maxRecordLimit;
    }

    /**
     * @param int $limit
     *
     * @return void
     */
    public function setMaxRecordLimit($limit)
    {
        $this->maxRecordLimit = $limit;
    }

    /**
     * @param int $nbLinks
     *
     * @return int[]
     */
    public function getLinks($nbLinks = 5)
    {
        $links = [];
        $tmp = $this->page - floor($nbLinks / 2);
        $check = $this->lastPage - $nbLinks + 1;
        $limit = ($check > 0) ? $check : 1;
        $begin = ($tmp > 0) ? (($tmp > $limit) ? $limit : $tmp) : 1;

        $i = (int)$begin;
        while (($i < $begin + $nbLinks) && ($i <= $this->lastPage)) {
            $links[] = $i++;
        }

        $this->currentMaxLink = count($links) ? $links[count($links) - 1] : 1;

        return $links;
    }

    /**
     * Test whether the number of results exceeds the max number of results per page
     *
     * @return bool true if the pager displays only a subset of the results
     */
    public function haveToPaginate()
    {
        return ($this->getMaxPerPage() !== 0 && $this->getNbResults() > $this->getMaxPerPage());
    }

    /**
     * Get the index of the first element in the page
     * Returns 1 on the first page, $maxPerPage +1 on the second page, etc
     *
     * @return int
     */
    public function getFirstIndex()
    {
        if ($this->page === 0) {
            return 1;
        }

        return ($this->page - 1) * $this->maxPerPage + 1;
    }

    /**
     * Get the index of the last element in the page
     * Always less than or equal to $maxPerPage
     *
     * @return int
     */
    public function getLastIndex()
    {
        if ($this->page === 0) {
            return $this->nbResults;
        }

        if (($this->page * $this->maxPerPage) >= $this->nbResults) {
            return $this->nbResults;
        }

        return $this->page * $this->maxPerPage;
    }

    /**
     * Get the total number of results of the query
     * This can be greater than $maxPerPage
     *
     * @return int
     */
    public function getNbResults()
    {
        return $this->nbResults;
    }

    /**
     * Set the total number of results of the query
     *
     * @param int $nb
     *
     * @return void
     */
    protected function setNbResults($nb)
    {
        $this->nbResults = $nb;
    }

    /**
     * Check whether the current page is the first page
     *
     * @return bool true if the current page is the first page
     */
    public function isFirstPage()
    {
        return $this->getPage() === $this->getFirstPage();
    }

    /**
     * Get the number of the first page
     *
     * @return int Always 1
     */
    public function getFirstPage()
    {
        return $this->nbResults === 0 ? 0 : 1;
    }

    /**
     * Check whether the current page is the last page
     *
     * @return bool true if the current page is the last page
     */
    public function isLastPage()
    {
        return $this->getPage() === $this->getLastPage();
    }

    /**
     * Get the number of the last page
     *
     * @return int
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     * Set the number of the first page
     *
     * @param int $page
     *
     * @return void
     */
    protected function setLastPage($page)
    {
        $this->lastPage = $page;
        if ($this->getPage() > $page) {
            $this->setPage($page);
        }
    }

    /**
     * Get the number of the current page
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set the number of the current page
     *
     * @param int $page
     *
     * @return void
     */
    public function setPage($page)
    {
        $this->page = (int)$page;
        if ($this->page <= 0 && $this->nbResults > 0) {
            // set first page, which depends on a maximum set
            $this->page = $this->getMaxPerPage() ? 1 : 0;
        }
    }

    /**
     * Get the number of the next page
     *
     * @return int
     */
    public function getNextPage()
    {
        return min($this->getPage() + 1, $this->getLastPage());
    }

    /**
     * Get the number of the previous page
     *
     * @return int
     */
    public function getPreviousPage()
    {
        return max($this->getPage() - 1, $this->getFirstPage());
    }

    /**
     * Get the maximum number results per page
     *
     * @return int
     */
    public function getMaxPerPage()
    {
        return $this->maxPerPage;
    }

    /**
     * Set the maximum number results per page
     *
     * @param int $max
     *
     * @return void
     */
    public function setMaxPerPage($max)
    {
        if ($max > 0) {
            $this->maxPerPage = $max;
            if ($this->page === 0) {
                $this->page = 1;
            }
        } elseif ($max === 0) {
            $this->maxPerPage = 0;
            $this->page = 0;
        } else {
            $this->maxPerPage = 1;
            if ($this->page === 0) {
                $this->page = 1;
            }
        }
    }

    /**
     * Check if the collection is empty
     *
     * @see Collection
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getResults()->isEmpty();
    }

    /**
     * @return \Propel\Runtime\Collection\CollectionIterator|\Traversable
     */
    public function getIterator()
    {
        return $this->getResults()->getIterator();
    }

    /**
     * Returns the number of items in the result collection.
     *
     * @see Countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->getResults());
    }

    /**
     * @param string $name
     * @param array $params
     *
     * @throws \Propel\Runtime\Exception\BadMethodCallException
     *
     * @return mixed
     */
    public function __call($name, $params)
    {
        try {
            /** @var callable $callback */
            $callback = [$this->getResults(), $name];

            return call_user_func_array($callback, $params);
        } catch (BadMethodCallException $exception) {
            throw new BadMethodCallException('Call to undefined method: ' . $name);
        }
    }
}
