<?php

/*
 *  $Id: PropelObjectsFormatter.php 1341 2009-11-29 13:57:27Z francois $
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
 * Objects formatter for Propel query
 * format() returns an array of Propel model objects
 *
 * @author     Francois Zaninotto
 * @version    $Revision: 1341 $
 * @package    propel.runtime.formatter
 */
class PropelObjectsFormatter extends PropelFormatter
{	
	public function format(PDOStatement $stmt)
	{
		$this->checkCriteria();
		$results = array();
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$results[] = $this->getObjectFromRow($row);
		}
		$stmt->closeCursor();
		return $results;
	}
	
	/**
	 * Gets the Propel object hydrated from a statement row
	 * @param array associative array indexed by column number,
	 *              as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 */
	public function getObjectFromRow($row)
	{
		$key = call_user_func(array($this->peer, 'getPrimaryKeyHashFromRow'), $row, 0);
		if (null === ($obj = call_user_func(array($this->peer, 'getInstanceFromPool'), $key))) {
			$obj = new $this->class();
			$obj->hydrate($row);
			call_user_func(array($this->peer, 'addInstanceToPool'), $obj, $key);
		}
		return $obj;
	}
}