<?php
/*
 *	$Id: ModelCriteria.php 1445 2010-01-11 21:11:03Z francois $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

/**
 * Implements a pager based on a ModelCriteria
 * The code from this class heavily borrows from symfony's sfPager class
 * 
 * @author		 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author		 François Zaninotto
 * @version		 $Revision$
 * @package		 propel.runtime.query
 */
class PropelModelPager implements IteratorAggregate, Countable
{
	protected
		$query					 = null,
		$page						 = 1,
		$maxPerPage			 = 10,
		$lastPage				 = 1,
		$nbResults			 = 0,
		$objects				 = null,
		$parameters			 = array(),
		$currentMaxLink	 = 1,
		$parameterHolder = null,
		$maxRecordLimit = false,
		$results         = null,
		$resultsCounter  = 0;

	public function __construct(Criteria $query, $maxPerPage = 10)
	{
		$this->setQuery($query);
		$this->setMaxPerPage($maxPerPage);
	}
	
	public function setQuery(Criteria $query)
	{
		$this->query = $query;
	}
	
	public function getQuery()
	{
		return $this->query;
	}

	public function init()
	{
		$hasMaxRecordLimit = ($this->getMaxRecordLimit() !== false);
		$maxRecordLimit = $this->getMaxRecordLimit();

		$qForCount = clone $this->getQuery();
		$count = $qForCount
			->offset(0)
			->limit(0)
			->clearGroupByColumns()
			->count();

		$this->setNbResults($hasMaxRecordLimit ? min($count, $maxRecordLimit) : $count);

		$q = $this->getQuery()
			->offset(0)
			->limit(0);

		if (($this->getPage() == 0 || $this->getMaxPerPage() == 0)) {
			$this->setLastPage(0);
		} else {
			$this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

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
	 * @return PropelObjectCollection A collection of results
	 */
	public function getResults()
	{
		if (null === $this->results) {
			$this->results = $this->getQuery()
				->setFormatter(ModelCriteria::FORMAT_OBJECT)
				->find();
		}
		return $this->results;
	}

	public function getCurrentMaxLink()
	{
		return $this->currentMaxLink;
	}

	public function getMaxRecordLimit()
	{
		return $this->maxRecordLimit;
	}

	public function setMaxRecordLimit($limit)
	{
		$this->maxRecordLimit = $limit;
	}

	public function getLinks($nb_links = 5)
	{
		$links = array();
		$tmp	 = $this->page - floor($nb_links / 2);
		$check = $this->lastPage - $nb_links + 1;
		$limit = ($check > 0) ? $check : 1;
		$begin = ($tmp > 0) ? (($tmp > $limit) ? $limit : $tmp) : 1;

		$i = (int) $begin;
		while (($i < $begin + $nb_links) && ($i <= $this->lastPage)) {
			$links[] = $i++;
		}

		$this->currentMaxLink = count($links) ? $links[count($links) - 1] : 1;

		return $links;
	}

	public function haveToPaginate()
	{
		return (($this->getMaxPerPage() != 0) && ($this->getNbResults() > $this->getMaxPerPage()));
	}

	public function getFirstIndex()
	{
		if ($this->page == 0) {
			return 1;
		} else {
			return ($this->page - 1) * $this->maxPerPage + 1;
		}
	}

	public function getLastIndex()
	{
		if ($this->page == 0) {
			return $this->nbResults;
		} else {
			if (($this->page * $this->maxPerPage) >= $this->nbResults) {
				return $this->nbResults;
			} else {
				return ($this->page * $this->maxPerPage);
			}
		}
	}

	public function getNbResults()
	{
		return $this->nbResults;
	}

	protected function setNbResults($nb)
	{
		$this->nbResults = $nb;
	}

	public function getFirstPage()
	{
		return 1;
	}

	public function getLastPage()
	{
		return $this->lastPage;
	}

	protected function setLastPage($page)
	{
		$this->lastPage = $page;
		if ($this->getPage() > $page) {
			$this->setPage($page);
		}
	}

	public function getPage()
	{
		return $this->page;
	}

	public function getNextPage()
	{
		return min($this->getPage() + 1, $this->getLastPage());
	}

	public function getPreviousPage()
	{
		return max($this->getPage() - 1, $this->getFirstPage());
	}

	public function setPage($page)
	{
		$this->page = intval($page);
		if ($this->page <= 0) {
			// set first page, which depends on a maximum set
			$this->page = $this->getMaxPerPage() ? 1 : 0;
		}
	}

	public function getMaxPerPage()
	{
		return $this->maxPerPage;
	}

	public function setMaxPerPage($max)
	{
		if ($max > 0) {
			$this->maxPerPage = $max;
			if ($this->page == 0) {
				$this->page = 1;
			}
		} else if ($max == 0) {
			$this->maxPerPage = 0;
			$this->page = 0;
		} else {
			$this->maxPerPage = 1;
			if ($this->page == 0) {
				$this->page = 1;
			}
		}
	}
	
	public function getIterator()
	{
		return $this->getResults()->getIterator();
	}

	/**
	 * Returns the total number of results.
	 *
	 * @see Countable
	 */
	public function count()
	{
		return $this->getNbResults();
	}

}
