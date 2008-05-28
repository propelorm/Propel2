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
 * PDO connection subclass that provides some basic support for query counting and logging.
 *
 * This class is ONLY intended for development use.  This class is also a work in-progress
 * and, as such, it should be expected that this class' API may change.  We will be looking
 * at ways to refactor this class to provide a more pluggable system for hooking in more query
 * informatics.  In the meantime, this class should illustrate how you can
 *
 * @author     Cameron Brunner <cameron.brunner@gmail.com>
 * @author     Hans Lellelid <hans@xmpl.org>
 * @author     Christian Abegg <abegg.ch@gmail.com>
 * @since      2006-09-22
 * @package    propel.util
 */
class DebugPDO extends PropelPDO {

	/**
	 * Count of queries performed
	 * @var        int
	 */
	protected $queryCount = 0;

	/**
	 * The statement class to use.
	 *
	 * @var        string
	 */
	protected $statementClass = 'DebugPDOStatement';

	/**
	 * Configured BasicLogger (or compatible) logger.
	 *
	 * @var        BasicLogger
	 */
	protected $logger;

	/**
	 * The log level to use for logging.
	 *
	 * @var        int
	 */
	private $logLevel = Propel::LOG_DEBUG;

	/**
	 * Construct a new DebugPDO connection.
	 *
	 * This method is overridden in order to specify a custom PDOStatement class.
	 *
	 * @param      string $dsn Connection DSN
	 * @param      string $username (optional
	 * @param      string $password
	 * @param      array $driver_options
	 * @throws     PDOException - if there is an error during connection initialization
	 */
	public function __construct($dsn, $username = null, $password = null, $driver_options = array())
	{
		parent::__construct($dsn, $username, $password, $driver_options);
		$this->configureStatementClass($suppress=true);
	}

	/**
	 * Configures the PDOStatement class for this connection.
	 * @param      boolean $suppressError Whether to suppress an exception if the statement class cannot be set.
	 * @throws     PropelException if the statement class cannot be set (and $suppressError is false)
	 */
	protected function configureStatementClass($suppressError = false)
	{
		// extending PDOStatement is not supported with persistent connections
		if (!$this->getAttribute(PDO::ATTR_PERSISTENT)) {
			$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array($this->getStatementClass(), array($this)));
		} elseif (!$suppressError) {
			throw new PropelException('Extending PDOStatement is not supported with persistent connections.');
		}
	}

	/**
	 * Sets the custom classname to use for PDOStatement.
	 *
	 * It is assumed that the specified classname has been loaded (or can be loaded
	 * on-demand with autoload).
	 *
	 * @param      string $classname
	 */
	public function setStatementClass($classname)
	{
		$this->statementClass = $classname;
		$this->configureStatementClass();
	}

	/**
	 * Gets the custom classname to use for PDOStatement.
	 * @return     string
	 */
	public function getStatementClass()
	{
		return $this->statementClass;
	}

	/**
	 * Gets the query count
	 * @return     int
	 * @throws     PropelException - if persistent connection is used (since unable to override PDOStatement in that case).
	 */
	public function getQueryCount()
	{
		// extending PDOStatement is not supported with persistent connections
		if ($this->getAttribute(PDO::ATTR_PERSISTENT)) {
			throw new PropelException('Extending PDOStatement is not supported with persistent connections. ' .
																'Count would be inaccurate, because we cannot count the PDOStatment::execute() calls. ' .
																'Either don\'t use persistent connections or don\'t call PropelPDO::getQueryCount()');
		}
		return $this->queryCount;
	}

	/**
	 * increments the query count
	 * @return     int
	 */
	public function incrementQueryCount()
	{
		$this->queryCount++;
	}

	/**
	 * Overrides PDO::prepare() to add logging.
	 * .
	 * @param      string $sql
	 * @param      array
	 * @return     PDOStatement
	 */
	public function prepare($sql, $driver_options = array())
	{
		$this->log('prepare: ' . $sql);
		return parent::prepare($sql, $driver_options);
	}

	/**
	 * overridden for query counting
	 * @return     int
	 */
	public function exec($sql)
	{
		$this->log('exec: ' . $sql);
		$this->incrementQueryCount();
		return parent::exec($sql);
	}

	/**
	 * Overridden for query counting and logging.
	 * @return     int
	 */
	public function query()
	{
		$args = func_get_args();
		$this->log('query: ' . $args[0]);
		$this->incrementQueryCount();
		return call_user_func_array(array($this, 'parent::query'), $args);
	}

	/**
	 * Sets the logging level to use for logging SQL statements.
	 *
	 * @param      int $level
	 */
	public function setLogLevel($level)
	{
		$this->logLevel = $level;
	}

	/**
	 * Sets a logger to use.
	 * @param      BasicLogger $logger A Logger with an API compatible with BasicLogger (or PEAR Log).
	 */
	public function setLogger($logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Logs the SQL using the Propel::log() method or registered logger class.
	 *
	 * @param      string $msg Message to log.
	 * @param      int $level (optional) Log level to use; will use setLogLevel() specified level by default. 
	 * @see        setLogger()
	 * @see
	 */
	public function log($msg, $level = null)
	{
		if ($level === null) {
			$level = $this->logLevel; 
		}
		if ($this->logger) {
			$this->logger->log($msg, $level);
		} else {
			Propel::log($msg, $level);
		}
	}

}
