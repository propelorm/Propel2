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
 * PDO connection subclass that provides some enhanced functionality needed by Propel.
 *
 * This class was designed to work around the limitation in PDO where attempting to begin
 * a transaction when one has already been begun will trigger a PDOException.  Propel
 * relies on the ability to create nested transactions, even if the underlying layer
 * simply ignores these (because it doesn't support nested transactions).
 *
 * The changes that this class makes to the underlying API include the addition of the
 * getNestedTransactionDepth() and isInTransaction() and the fact that beginTransaction()
 * will no longer throw a PDOException (or trigger an error) if a transaction is already
 * in-progress.
 *
 * @author     Cameron Brunner <cameron.brunner@gmail.com>
 * @author     Hans Lellelid <hans@xmpl.org>
 * @author     Christian Abegg <abegg.ch@gmail.com>
 * @since      2006-09-22
 * @package    propel.util
 */
class PropelPDO extends PDO {

	/**
	 * The current transaction depth.
	 * @var        int
	 */
	protected $nestedTransactionCount = 0;

	/**
	 * Array of slave connections
	 *
	 * keys: param, name, con (only after initialisation)
	 */
	protected $slaves = array();

	/**
	 *  A single slave connection
	 */
	protected $slave = null;

	/**
	 *  Use only the master connection
	 */
	protected $useMasterConnection = false;

	/**
	 * Gets the current transaction depth.
	 * @return     int
	 */
	public function getNestedTransactionCount()
	{
		return $this->nestedTransactionCount;
	}

	/**
	 * Set the current transaction depth.
	 * @param      int $v The new depth.
	 */
	protected function setNestedTransactionCount($v)
	{
		$this->nestedTransactionCount = $v;
	}

	/**
	 * Decrements the current transaction depth by one.
	 */
	protected function decrementNestedTransactionCount()
	{
		$this->nestedTransactionCount--;
	}

	/**
	 * Increments the current transaction depth by one.
	 */
	protected function incrementNestedTransactionCount()
	{
		$this->nestedTransactionCount++;
	}

	/**
	 * Is this PDO connection currently in-transaction?
	 * This is equivalent to asking whether the current nested transaction count
	 * is greater than 0.
	 * @return     boolean
	 */
	public function isInTransaction()
	{
		return ($this->getNestedTransactionCount() > 0);
	}

	/**
	 * Overrides PDO::beginTransaction() to prevent errors due to already-in-progress transaction.
	 */
	public function beginTransaction()
	{
		$return = true;
		$opcount = $this->getNestedTransactionCount();
		if ( $opcount === 0 ) {
			$return = parent::beginTransaction();
		}
		$this->incrementNestedTransactionCount();
		return $return;
	}

	/**
	 * Overrides PDO::commit() to only commit the transaction if we are in the outermost
	 * transaction nesting level.
	 */
	public function commit()
	{
		$return = true;
		$opcount = $this->getNestedTransactionCount();
		if ($opcount > 0) {
			if ($opcount === 1) {
				$return = parent::commit();
			}
			$this->decrementNestedTransactionCount();
		}
		return $return;
	}

	/**
	 * Overrides PDO::rollback() to only rollback the transaction if we are in the outermost
	 * transaction nesting level.
	 */
	public function rollback()
	{
		$return = true;
		$opcount = $this->getNestedTransactionCount();
		if ($opcount > 0) {
			if ($opcount === 1) {
				$return = parent::rollback();
			}
			$this->decrementNestedTransactionCount();
		}
		return $return;
	}

	/**
	 * Overrides PDO::prepare() to add logging and split r/w queries
	 */
	public function prepare($sql, $driver_options = array())
	{
		Propel::log($sql, Propel::LOG_DEBUG);
		if ($this->isForSlave($sql)) {
			if ($slave = $this->getSlave()) {
				return $slave->prepare($sql, $driver_options);
			}
		}
		return parent::prepare($sql, $driver_options);
	}

	/**
	 * Overrides PDO::query() to add logging and split r/w queries
	 */
	public function query($sql, $fetch = null, $input3=null, $input4=null) {
		Propel::log($sql, Propel::LOG_DEBUG);
		if ($this->isForSlave($sql)) {
			if ($slave = $this->getSlave()) {
				return $slave->query($sql, $fetch, $input3, $input4);
			}
		}
		return parent::query($sql, $fetch, $input3, $input4);
	}

	/**
	 * Overrides PDO::exec() to add logging and split r/w queries
	 */
	public function exec($sql) {
		Propel::log($sql, Propel::LOG_DEBUG);
		if ($this->isForSlave($sql)) {
			if ($slave = $this->getSlave()) {
				return $slave->exec($sql);
			}
		}
		return parent::exec($sql);
	}

	/**
	 * Adds the configuration for a slave connection
	 *
	 * @param      array slave param from config
	 * @param      string name of the connection
	 * @return     void
	 */
	public function addSlave($slaveparam, $name) {
		$newIndex = count($this->slaves);
		$this->slaves[$newIndex]['param'] = $slaveparam;
		$this->slaves[$newIndex]['name'] = $name;
	}

	/**
	 * Gets one of the slave connections for read only access
	 *
	 * @return     SlavePDO or false if there are no slaves
	 */
	private function getSlave() {

		if (isset($this->slave)) return $this->slave;		// slave already initialised
		if (count($this->slaves) == 0) return false;	// return the plain PDO object to avoid endless loops

		$random = mt_rand(0, count($this->slaves)-1);
		if (isset($this->slaves[$random]['con'])) {
			$this->slave = $this->slaves[$random]['con'];
		}
		else {
			$this->slaves[$random]['con'] = Propel::initConnection($this->slaves[$random]['param'], $this->slaves[$random]['name'], Propel::CLASS_SLAVE_PDO);
			$this->slave = $this->slaves[$random]['con'];
		}
		return $this->slave;
	}

	/**
	 * Checks if a sql query should be handled by the slave connection
	 *
	 * @return     boolean
	 */
	private function isForSlave($sql) {

		// return false if the use of the master connection is forced
		if ($this->useMasterConnection) return false;

		// return false if a transaction is open
		$opcount = $this->getNestedTransactionCount();
		if ($opcount > 0) return false;

		// check if sql is read only
		return $this->isReadOnly($sql);
	}

	/**
	 * Checks if a sql query is read only (e.g. starts with "select")
	 *
	 * @return     boolean
	 */
	public static function isReadOnly($sql) {

		// analyse sql
		$result = stripos($sql, "SELECT");
		if ($result === false) {
			// no select found
			return false;
		}
		else if ($result == 0) {
			// sql starts with select
			return true;
		}
		else {
			// select is somewhere else in string
			return false;
		}
	}

	/**
	 * Forces the use of the master connection only
	 *
	 * @param      boolean use master connection only
	 * @return     void
	 */
	public function setUseMasterConnection($useMasterConnection) {
		if (is_bool($useMasterConnection)) {
			$this->useMasterConnection = $useMasterConnection;
		}
		else throw new PropelException("Parameter of setUseMasterConnection must be boolean");
	}
}
