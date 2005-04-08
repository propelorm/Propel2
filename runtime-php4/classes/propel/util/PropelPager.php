<?php
/*
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
 *  PropelPager [FOR PHP4]
 *  Example Usage:
 *
 *  require_once 'propel/util/PropelPager.php';
 *  require_once 'PEACH/Propel/Poem/poemPeer.php';
 *
 *  $c = new Criteria();
 *  $c->addDescendingOrderByColumn(poemPeer::SID);
 *
 *  // with join
 *  $pager = new PropelPager($c, 'poemPeer', 'doSelectJoinPoemUsers', 1, 50);
 *
 *  // without Join 
 *
 *  $pager = new PropelPager($c, 'poemPeer', 'doSelect', 1, 50);
 *
 * Some template:
 *
 * <p>
 * Total Pages: <?=$pager->getTotalPages()?>  Total Records: <?=$pager->getTotalRecordCount()?>
 * </p>
 * <table>
 * <tr>
 * <td>
 * <?if($link = $pager->getFirstPage):?>
 * <a href="somescript?page=<?=$link?>"><?=$link?></a>|
 * <?endif?>
 * </td>
 * <td>
 * <?if($link = $pager->getPrev()):?>
 * <a href="somescript?page=<?=$link?>">Previous</a>|
 * <?endif?>
 * </td>
 * <td>
 * <?foreach($pager->getPrevLinks() as $link):?>
 * <a href="somescript?page=<?=$link?>"><?=$link?></a>|
 * <?endforeach?>
 * </td>
 * <td><?=$pager->getPage()?></td>
 * <td>
 * <?foreach($pager->getNextLinks() as $link):?>
 * | <a href="somescript?page=<?=$link?>"><?=$link?></a>
 * <?endforeach?>
 * </td>
 * <td>
 * <?if($link = $pager->getNext()):?>
 * <a href="somescript?page=<?=$link?>">Last</a>|
 * <?endif?>
 * </td>
 * <td>
 * <?if($link = $pager->getLastPage()):?>
 * <a href="somescript?page=<?=$link?>"><?=$link?></a>|
 * <?endif?>
 * </td>
 * </tr>
 * </table>
 * <table id="latestPoems">
 * <tr>
 * <th>Title</th>
 * <th>Auteur</th>
 * <th>Date</th>
 * <th>comments</th>
 * </tr>
 * <?foreach($pager->getResult() as $poem):?>
 * <tr>
 * <td><?=$poem->getTitle()?></td>
 * <td><?=$poem->getPoemUsers()->getUname()?></td>
 * <td><?=$poem->getTime()?></td>
 * <td><?=$poem->getComments()?></td>
 * </tr>
 * <?endforeach?>
 * </table>
 *
 * 
 * @author	Rob Halff <info@rhalff.com>
 * @author	Steve Lianoglou [inital port to php4] <lists@arachnedesign.net>
 * @version   $Revision: 1.1 $
 * @copyright Copyright (c) 2004 Rob Halff: LGPL - See LICENCE
 * @package   propel.util 
 */
class PropelPager {
	
	/* private */ var $recordCount;
	/* private */ var $pages;
	/* private */ var $peerClass;
	/* private */ var $peerMethod;
	/* private */ var $criteria;
	/* private */ var $page;
	/* private */ var $rs = null;
	
	/** @var int Start row (offset) */
	/* protected */ var $start = 0;
	
	/** @var int Max rows to return (0 means all) */
	/* protected */ var $max = 0;
	
	/**
	 * Create a new Propel Pager.
	 * @param Criteria $c
	 * @param string $peerClass 
	 * @param string $peerMethod
	 * @param int $page
	 * @param int $rowsPerPage
	 */
	/* public */ function PropelPager($c, $peerClass, $peerMethod, $page = 1, $rowsPerPage = 25)
	{
		$this->setCriteria($c);
		$this->setPeerClass($peerClass);
		$this->setPeerMethod($peerMethod);
		$this->setPage($page);
		$this->setRowsPerPage($rowsPerPage);
	}
	
	/**
	 * Set the criteria for this pager.
	 * @param Criteria $c
	 * @return void
	 */
	/* public */ function setCriteria($c)
	{
		$this->criteria = $c;
	}
	
	/**
	 * Return the Criteria object for this pager.
	 * @return Criteria
	 */
	/* public */ function getCriteria()
	{
		return $this->criteria;
	}
	
	/**
	 * Set the Peer Classname
	 * 
	 * @param string $class
	 * @return void
	 */
	/* public */ function setPeerClass($class)
	{
		$this->peerClass = $class;
	}

	/**
	 * Return the Peer Classname.
	 * @return string
	 */
	/* public */ function getPeerClass()
	{
		return $this->peerClass;
	}
	
	/**
	 * Set the Peer Method 
	 * 
	 * @param string $class
	 * @return void
	 */
	/* public */ function setPeerMethod($method)
	{
		$this->peerMethod = $method;
	}

	/**
	 * Return the Peer Method.
	 * @return string
	 */
	/* public */ function getPeerMethod()
	{
		return $this->peerMethod;
	}
	
	/**
	 * Get the paged resultset 
	 * 
	 * Main method which creates a paged result set based on the criteria 
	 * and the requested peer select method.
	 * the eval is needed here because something like {$class::$method}();
	 * just doesn't work
	 * 
	 * @return mixed $rs 
	 */
	/* public */ function getResult()
	{
		if(!isset($this->rs)) {
			$this->doRs();
		}

		return $this->rs;
	}
	
	/* private */ function doRs()
	{   
		$this->criteria->setOffset($this->start);
		$this->criteria->setLimit($this->max);
		$this->rs = call_user_func(array($this->peerClass, $this->peerMethod), $this->criteria);
	}
	
	/**
	 * Get the first page 
	 * 
	 * For now I can only think of returning 1 always.
	 * It should probably return 0 if there are no pages
	 * 
	 * @return int 1 
	 */
	/* public */ function getFirstPage()
	{
		return '1';
	}
	
	/**
	 * Convenience method to indicate whether current page is the first page.
	 * 
	 * @return boolean
	 */
	/* public */ function atFirstPage()
	{
		return ( $this->getPage() == $this->getFirstPage() );
	}
	
	/**
	 * Get last page 
	 * 
	 * @return int $lastPage 
	 */
	/* public */ function getLastPage()
	{
		$lastPage = $this->getTotalPages();
		return $lastPage;
	}
	
	/**
	 * Convenience method to indicate whether current page is the last page.
	 * 
	 * @return boolean
	 */
	/* public */ function atLastPage()
	{
		return ( $this->getPage() == $this->getLastPage() );
	}
	
	/**
	 * get total pages 
	 * 
	 * @return int $this->pages
	 */
	/* public */ function getTotalPages() {
		if(!isset($this->pages)) {
			$recordCount = $this->getTotalRecordCount();
			if($this->max > 0) {
					$this->pages = ceil($recordCount/$this->max);
			} else {
					$this->pages = 0;
			}
		}
		return $this->pages;
	}
	
	/**
	 * get an array of previous id's  
	 * 
	 * @param int $range
	 * @return array $links
	 */
	/* public */ function getPrevLinks($range = 5)
	{
		$total = $this->getTotalPages();
		$start = $this->getPage() - 1;
		$end = $this->getPage() - $range;
		$first =  $this->getFirstPage();
		$links = array();
		for($i=$start; $i>$end; $i--) {
			if($i < $first) {
					break;
			}
			$links[] = $i;
		}

		return array_reverse($links);
	}
	
	/**
	 * get an array of next id's  
	 * 
	 * @param int $range
	 * @return array $links
	 */
	/* public */ function getNextLinks($range = 5)
	{
		$total = $this->getTotalPages();
		$start = $this->getPage() + 1;
		$end = $this->getPage() + $range;
		$last =  $this->getLastPage();
		$links = array();
		for($i=$start; $i<$end; $i++) {
			if($i > $last) {
					break;
			}
			$links[] = $i;
		}

		return $links;
	}	
	
	/**
	 * Returns whether last page is complete
	 *
	 * @return bool Last age complete or not
	 */
	/* public */ function isLastPageComplete()
	{
		return !($this->getTotalRecordCount() % $this->max);
	}
	
	/**
	 * get previous id  
	 * 
	 * @return mixed $prev
	 */
	/* public */ function getPrev() {
		// Prev link
		if($this->getPage() != $this->getFirstPage()) {
				$prev = $this->getPage() - 1;
		} else {
				$prev = false;
		}
		return $prev;
	}
	
	/**
	 * get next id  
	 * 
	 * @return mixed $next
	 */
	/* public */ function getNext() {
		// Prev link
		if($this->getPage() != $this->getLastPage()) {
				$next = $this->getPage() + 1;
		} else {
				$next = false;
		}
		return $next;
	}
	
	/**
	 * Set the current page number (First page is 1).
	 * @param int $page
	 * @return void
	 */
	/* public */ function setPage($page)
	{
		$this->page = $page;
		// (re-)calculate start rec
		$this->calculateStart();
	}
	
	/**
	 * Get current page.
	 * @return int
	 */
	/* public */ function getPage()
	{
		return $this->page;
	}
	
	/**
	 * Set the number of rows per page.
	 * @param int $r
	 */
	/* public */ function setRowsPerPage($r)
	{
		$this->max = $r;
		// (re-)calculate start rec
		$this->calculateStart();
	}
	
	/**
	 * Get number of rows per page.
	 * @return int
	 */
	/* public */ function getRowsPerPage()
	{
		return $this->max;
	}
	
	/**
	 * Calculate startrow / max rows based on current page and rows-per-page.
	 * @return void
	 */
	/* private */ function calculateStart()
	{
		$this->start = ( ($this->page - 1) * $this->max );
	}
	
	/**
	 * Gets the total number (un-LIMITed) of records.
	 * 
	 * This method will perform a query that executes un-LIMITed query. 
	 *
	 * @return int Total number of records - disregarding page, maxrows, etc.
	 * should @throw SQLException
	 */
	/* public */ function getTotalRecordCount()
	{	
		if(!isset($this->rs)) {
			$this->doRs();
		}

		if(empty($this->recordCount)) {
			// $countSql = constant($this->peerClass.'::COUNT()').' AS total';
			$evalString = "\$countSql = " . $this->peerClass . "::COUNT() . ' AS total';";
			eval($evalString);
			$this->criteria->clearSelectColumns();
			$this->criteria->clearOrderByColumns();
			$this->criteria->setLimit(0);
			$this->criteria->setOffset(0);
			$this->criteria->addSelectColumn($countSql);
			$params = array();
			$rs = basePeer::doSelect($this->criteria);
			$rs->next();
			$this->recordCount = $rs->getInt(1);
		}

		return $this->recordCount;
	}
	
	/**
	 * Sets the start row or offset.
	 * @param int $v
	 */
	/* public */ function setStart($v)
	{
		$this->start = $v;
	}
	
	/**
	 * Sets max rows (limit).
	 * @param int $v
	 * @return void
	 */
	/* public */ function setMax($v)
	{
		$this->max = $v;
	}

} 
?>
