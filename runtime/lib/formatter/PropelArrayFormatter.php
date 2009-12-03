<?php

/*
 *  $Id$
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
 * Array formatter for Propel query
 * format() returns an array of associative arrays
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
class PropelArrayFormatter extends PropelFormatter
{
	protected $currentObject = null;
	
	public function format(PDOStatement $stmt)
	{
		$results = array();
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			// instance pooling is not used here
			$this->getCurrentObject()->hydrate($row);
			$results[] = $this->getCurrentObject()->toArray();
		}
		$this->currentObject = null;
		$stmt->closeCursor();
		return $results;
	}

	public function formatOne(PDOStatement $stmt)
	{
		if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			// instance pooling is not used here
			$this->getCurrentObject()->hydrate($row);
			$result = $this->getCurrentObject()->toArray();
		} else {
			$result = null;
		}
		$this->currentObject = null;
		$stmt->closeCursor();
		return $result;
	}
	
	protected function getCurrentObject()
	{
		if(null === $this->currentObject) {
			$this->checkCriteria();
			$class = $this->class;
			$this->currentObject = new $class();
		}
		return $this->currentObject;
	}	
}