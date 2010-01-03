<?php

/*
 *  $Id: PropelObjectFormatter.php 1380 2009-12-28 10:11:46Z francois $
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
 * Object formatter for Propel query
 * format() returns a PropelOnDemandCollection that hydrates objects as the use iterates on the collection
 * This formatter consumes less memory than the PropelObjectFormatter, but doesn't use Instance Pool
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
class PropelOnDemandFormatter extends PropelObjectFormatter
{
	protected $collectionName = 'PropelOnDemandCollection';
	
	public function format(PDOStatement $stmt)
	{
		$this->checkCriteria();
		$class = $this->collectionName;
		$collection = new $class();
		$collection->setModel($this->getCriteria()->getModelName());
		$collection->setFormatter($this);
		$collection->setStatement($stmt);
		
		return $collection;
	}
	
	/**
	 * Gets a Propel object hydrated from a selection of columns in statement row
	 *
	 * @param     array  $row associative array indexed by column number,
	 *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 * @param     string $class The classname of the object to create
	 * @param     int    $col The start column for the hydration (modified)
	 *
	 * @return    BaseObject
	 */
	public function getSingleObjectFromRow($row, $class, $peer = null, &$col = 0)
	{
		$obj = $this->getWorkerObject($col, $class);
		$col = $obj->hydrate($row, $col);
		
		return $obj;
	}

	
}