<?php
/*
 *  $Id: Transaction.php,v 1.17 2005/03/25 16:15:43 dzuelke Exp $
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
 * Utility class to make it easier to begin, commit, and rollback transactions.
 * 
 * This can be used to handle cases where transaction support is optional.
 * The second parameter of beginOptionalTransaction() will determine with a transaction 
 * is used or not. If a transaction is not used, the commit and rollback methods
 * do not have any effect. Instead it simply makes the logic easier to follow
 * by cutting down on the if statements based solely on whether a transaction
 * is needed or not.
 * 
 * @author Hans Lellelid <hans@xmpl.org> (Propel) 
 * @author Stephen Haberman <stephenh@chase3000.com> (Torque)
 * @version $Revision: 1.17 $
 * @package propel.util
 */
class Transaction {

    /**
     * Begin a transaction.  This method will fallback gracefully to
     * return a normal connection, if the database being accessed does
     * not support transactions.
     *
     * @param string $dbName Name of database.
     * @return Connection The Connection for the transaction.
     * @throws PropelException
     */
    public static function begin($dbName)
    {
        $con = Propel::getConnection($dbName);
        try {
            $con->setAutoCommit(false);
        } catch (SQLException $e) {
            throw new PropelException($e);
        }
        return $con;
    }

    /**
     * Begin a transaction.  This method will fallback gracefully to
     * return a normal connection, if the database being accessed does
     * not support transactions.
     *
     * @param sring $dbName Name of database.
     * @param boolean $useTransaction If false, a transaction won't be used.
     * @return Connection The Connection for the transaction.
     * @throws PropelException
     */
    public static function beginOptional($dbName, $useTransaction)
    {
        $con = Propel::getConnection($dbName);
        try {
            if ($useTransaction) {
                $con->setAutoCommit(false);
            }
        } catch (SQLException $e) {
            throw new PropelException($e);
        }
        return $con;
    }

    /**
     * Commit a transaction.  This method takes care of releasing the
     * connection after the commit.  In databases that do not support
     * transactions, it only returns the connection.
     *
     * @param Connection $con The Connection for the transaction.
     * @return void
     * @throws PropelException
     */
    public static function commit($con)
    {
        if ($con === null) {
            throw new PropelException(
                    "Connection object was null. "
                    . "This could be due to a misconfiguration. "
                    . "Check the logs and Propel properties "
                    . "to better determine the cause.");
        }
        try {
            if ($con->getAutoCommit() === false) {
                $con->commit();
                $con->setAutoCommit(true);
            }
        } catch (SQLException $e) {
            throw new PropelException($e);
        }
    }

    /**
     * Roll back a transaction in databases that support transactions.
     * It also releases the connection. In databases that do not support
     * transactions, this method will log the attempt and release the
     * connection.
     *
     * @param Connection $con The Connection for the transaction.
     * @return void
     * @throws PropelException
     */
    public static function rollback($con)
    {
        if ($con === null) {
            throw new PropelException(
                    "Connection object was null. "
                    . "This could be due to a misconfiguration. "
                    . "Check the logs and Propel properties "
                    . "to better determine the cause.");
        }

        try {
            if ($con->getAutoCommit() === false) {
                $con->rollback();
                $con->setAutoCommit(true);
            }
        } catch (SQLException $e) {
            Propel::log(
                    "An attempt was made to rollback a transaction "
                    . "but the database did not allow the operation to be "
                    . "rolled back: " . $e->getMessage(), Propel::LOG_ERR);
            throw new PropelException($e);
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
        } catch (PropelException $e) {
            Propel::log("An error occured during rollback: " . $e->getMessage(), Propel::LOG_ERR);
        }
    }


}
