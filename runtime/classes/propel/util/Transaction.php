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
 * PDO utility class to help with managing transactions.
 *
 * This can be used to handle cases where transaction support is optional.
 *
 * The second parameter of beginOptionalTransaction() will determine with a transaction
 * is used or not. If a transaction is not used, the commit and rollback methods
 * do not have any effect. Instead it simply makes the logic easier to follow
 * by cutting down on the if statements based solely on whether a transaction
 * is needed or not.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @version $Revision$
 * @package propel.util
 */
class Transaction {

	/**
	 * Stores the transaction opcount so that we can emulate support for nested transactions.
	 */
	private static $txMap = array();

	/**
	 * Checks whether THIS CLASS has started a transaction for the passed-in PDO connection object.
	 *
	 * The transactions are stored keyed by the string representation of the object (e.g. "Object id #14").
	 *
	 * @param PDO $con
	 */
	public static function isInTransaction(PDO $con)
	{
		return (self::getOpcount($con) > 0);
	}

	/**
	 * Returns the current nested transaction depth.
	 * @param PDO $con
	 * @return int
	 */
	private static function getOpcount(PDO $con)
	{
		$txkey = (string)$con;
		if (!isset(self::$txMap[$txkey])) { self::$txMap[$txkey] = 0; }
		return self::$txMap[$txkey];
	}

	/**
	 * Increments the current nested transaction depth.
	 * @param PDO $con
	 */
	private static function incrementOpcount(PDO $con)
	{
		self::$txMap[(string)$con]++;
	}

	/**
	 * Decrements the current nested transaction depth.
	 * @param PDO $con
	 */
	private static function decrementOpcount(PDO $con)
	{
		self::$txMap[(string)$con]--;
	}

	/**
	 * Begin a transaction.
	 *
	 * @param $con PDO The Connection for the transaction.
	 * @throws PDOException
	 */
	public static function begin(PDO $con)
	{
		if (self::getOpcount($con) === 0) {
			$con->beginTransaction();
		}
		self::incrementOpcount($con);
	}

	/**
	 * Convenience method to begin an optional transaction.
	 *
	 * @param sring $dbName Name of database.
	 * @param boolean $useTransaction If false, a transaction won't be used.
	 * @throws PropelException
	 */
	public static function beginOptional(PDO $con, $useTransaction)
	{
		if ($useTransaction) {
			self::begin($con);
		}
	}

	/**
	 * Commit a transaction.
	 *
	 * This method commits a transaction if it is the outermost transaction - otherwise,
	 * nothing happens.
	 *
	 * @param PDO $con The Connection for the transaction.
	 * @return void
	 * @throws PropelException
	 */
	public static function commit(PDO $con)
	{
		$opcount = self::getOpcount($con);
		if ($opcount > 0) {
			if ($opcount === 1) {
				$con->commit();
			}
			self::decrementOpcount($con);
		}
	}

	/**
	 * Roll back a transaction in databases that support transactions.
	 * It also releases the connection. In databases that do not support
	 * transactions, this method will log the attempt and release the
	 * connection.
	 *
	 * @param PDO $con The Connection for the transaction.
	 * @return void
	 * @throws PropelException
	 */
	public static function rollback(PDO $con)
	{
		$opcount = self::getOpcount($con);
		if ($opcount > 0) {
			if ($opcount === 1) {
				try {
					$con->rollback();
				} catch (PDOException $e) {
					Propel::log(
							"An attempt was made to rollback a transaction "
							. "but the database did not allow the operation to be "
							. "rolled back: " . $e->getMessage(), Propel::LOG_ERR);
					throw new $e;
				}
			}
			self::decrementOpcount($con);
		}
	}

	/**
	 * Roll back a transaction without throwing errors if they occur.
	 *
	 * @param Connection $con The Connection for the transaction.
	 * @return void
	 */
	public static function safeRollback($con)
	{
		try {
			Transaction::rollback($con);
		} catch (PDOException $e) {
			Propel::log("An error occured during rollback: " . $e->getMessage(), Propel::LOG_ERR);
		}
	}


}
