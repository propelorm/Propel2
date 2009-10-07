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
 * and, as such, it should be expected that this class' API may change.
 * 
 * The following runtime configuration items affect the behaviour of this class:
 * 
 * - debugpdo.logging.enabled (default: true)
 *   Should any logging take place
 * 
 * - debugpdo.logging.innerglue (default: ": ")
 *   String to use for combining the title of a detail and its value
 * 
 * - debugpdo.logging.outerglue (default: " | ")
 *   String to use for combining details together on a log line
 * 
 * - debugpdo.logging.realmemoryusage (default: false)
 *   Parameter to memory_get_usage() and memory_get_peak_usage() calls 
 * 
 * - debugpdo.logging.methods (default: DebugPDO::$defaultLogMethods)
 *   An array of method names ("Class::method") to be included in method call logging
 * 
 * - debugpdo.logging.onlyslow (default: false)
 *   Suppress logging of non-slow queries.
 * 
 * - debugpdo.logging.details.slow.enabled (default: false)
 *   Enables flagging of slow method calls
 * 
 * - debugpdo.logging.details.slow.threshold (default: 0.1)
 *   Method calls taking more seconds than this threshold are considered slow 
 * 
 * - debugpdo.logging.details.time.enabled (default: false)
 *   Enables logging of method execution times
 * 
 * - debugpdo.logging.details.time.precision (default: 3)
 *   Determines the precision of the execution time logging
 * 
 * - debugpdo.logging.details.time.pad (default: 10)
 *   How much horizontal space to reserve for the execution time on a log line
 * 
 * - debugpdo.logging.details.mem.enabled (default: false)
 *   Enables logging of the instantaneous PHP memory consumption
 * 
 * - debugpdo.logging.details.mem.precision (default: 1)
 *   Determines the precision of the memory consumption logging
 * 
 * - debugpdo.logging.details.mem.pad (default: 9)
 *   How much horizontal space to reserve for the memory consumption on a log line
 * 
 * - debugpdo.logging.details.memdelta.enabled (default: false)
 *   Enables logging differences in memory consumption before and after the method call
 * 
 * - debugpdo.logging.details.memdelta.precision (default: 1)
 *   Determines the precision of the memory difference logging
 * 
 * - debugpdo.logging.details.memdelta.pad (default: 10)
 *   How much horizontal space to reserve for the memory difference on a log line
 * 
 * - debugpdo.logging.details.mempeak.enabled (default: false)
 *   Enables logging the peak memory consumption thus far by the currently executing PHP script
 * 
 * - debugpdo.logging.details.mempeak.precision (default: 1)
 *   Determines the precision of the memory peak logging
 * 
 * - debugpdo.logging.details.mempeak.pad (default: 9)
 *   How much horizontal space to reserve for the memory peak on a log line
 * 
 * - debugpdo.logging.details.querycount.enabled (default: false)
 *   Enables logging of the number of queries performed by the DebugPDO instance thus far
 * 
 * - debugpdo.logging.details.querycount.pad (default: 2)
 *   How much horizontal space to reserve for the query count on a log line
 * 
 * - debugpdo.logging.details.method.enabled (default: false)
 *   Enables logging of the name of the method call
 * 
 * - debugpdo.logging.details.method.pad (default: 28)
 *   How much horizontal space to reserve for the method name on a log line
 * 
 * The order in which the logging details are enabled is significant, since it determines the order in
 * which they will appear in the log file.
 * 
 * @example    // Enable simple query profiling, flagging calls taking over 1.5 seconds as slow:
 *             $config = Propel::getConfiguration(PropelConfiguration::TYPE_OBJECT);
 *             $config->setParameter('debugpdo.logging.details.slow.enabled', true);
 *             $config->setParameter('debugpdo.logging.details.slow.threshold', 1.5);
 *             $config->setParameter('debugpdo.logging.details.time.enabled', true);
 * 
 * @author     Francois Zaninotto
 * @author     Cameron Brunner <cameron.brunner@gmail.com>
 * @author     Hans Lellelid <hans@xmpl.org>
 * @author     Christian Abegg <abegg.ch@gmail.com>
 * @author     Jarno Rantanen <jarno.rantanen@tkk.fi>
 * @since      2006-09-22
 * @package    propel.util
 */
class DebugPDO extends PropelPDO
{
	const DEFAULT_SLOW_THRESHOLD        = 0.1;
	const DEFAULT_ONLYSLOW_ENABLED      = false;
	
	/**
	 * Count of queries performed.
	 * 
	 * @var        int
	 */
	protected $queryCount = 0;
	
	/**
	 * SQL code of the latest performed query.
	 * 
	 * @var        string
	 */
	protected $lastExecutedQuery;

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
	 * The default value for runtime config item "debugpdo.logging.methods".
	 *
	 * @var        array
	 */
	protected static $defaultLogMethods = array(
		'DebugPDO::exec',
		'DebugPDO::query',
		'DebugPDOStatement::execute',
	);
	
	/**
	 * Creates a DebugPDO instance representing a connection to a database.
	 *
	 * This method is overridden in order to specify a custom PDOStatement class and to implement logging.
	 *
	 * @param      string $dsn Connection DSN.
	 * @param      string $username (optional) The user name for the DSN string.
	 * @param      string $password (optional) The password for the DSN string.
	 * @param      array $driver_options (optional) A key=>value array of driver-specific connection options.
	 * @throws     PDOException if there is an error during connection initialization.
	 */
	public function __construct($dsn, $username = null, $password = null, $driver_options = array())
	{
		$debug = $this->getDebugSnapshot();
		
		parent::__construct($dsn, $username, $password, $driver_options);
		
		$this->configureStatementClass($suppress=true);
		$this->log('', null, __METHOD__, $debug);
	}

	/**
	 * Configures the PDOStatement class for this connection.
	 * 
	 * @param      boolean $suppressError Whether to suppress an exception if the statement class cannot be set.
	 * @throws     PropelException if the statement class cannot be set (and $suppressError is false).
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
	 * @param      string $classname Name of the statement class to use.
	 */
	public function setStatementClass($classname)
	{
		$this->statementClass = $classname;
		$this->configureStatementClass();
	}

	/**
	 * Gets the custom classname to use for PDOStatement.
	 * 
	 * @return     string
	 */
	public function getStatementClass()
	{
		return $this->statementClass;
	}

	/**
	 * Returns the number of queries this DebugPDO instance has performed on the database connection.
	 * 
	 * When using DebugPDOStatement as the statement class, any queries by DebugPDOStatement instances
	 * are counted as well.
	 * 
	 * @return     int
	 * @throws     PropelException if persistent connection is used (since unable to override PDOStatement in that case).
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
	 * Increments the number of queries performed by this DebugPDO instance.
	 * 
	 * Returns the original number of queries (ie the value of $this->queryCount before calling this method).
	 * 
	 * @return     int
	 */
	public function incrementQueryCount()
	{
		$this->queryCount++;
	}

	/**
	 * Get the SQL code for the latest query executed by Propel
	 * 
	 * @return string Executable SQL code
	 */
	public function getLastExecutedQuery() 
	{ 
		return $this->lastExecutedQuery; 
	}
	
	/**
	 * Set the SQL code for the latest query executed by Propel
	 * 
	 * @param string $query Executable SQL code
	 */
	public function setLastExecutedQuery($query) 
	{ 
		$this->lastExecutedQuery = $query; 
	}

	/**
	 * Prepares a statement for execution and returns a statement object.
	 * 
	 * Overrides PDO::prepare() to add logging and query counting.
	 * 
	 * @param      string $sql This must be a valid SQL statement for the target database server.
	 * @param      array One or more key=>value pairs to set attribute values for the PDOStatement object that this method returns.
	 * @return     PDOStatement
	 */
	public function prepare($sql, $driver_options = array())
	{
		$debug	= $this->getDebugSnapshot();
		$return	= parent::prepare($sql, $driver_options);
		
		$this->log($sql, null, __METHOD__, $debug);
		
		return $return;
	}

	/**
	 * Execute an SQL statement and return the number of affected rows.
	 * 
	 * Overridden for query counting and logging.
	 * 
	 * @return     int
	 */
	public function exec($sql)
	{
		$debug	= $this->getDebugSnapshot();
		$return	= parent::exec($sql);

		$this->log($sql, null, __METHOD__, $debug);
		$this->setLastExecutedQuery($sql); 	
		$this->incrementQueryCount();
		
		return $return;
	}

	/**
	 * Executes an SQL statement, returning a result set as a PDOStatement object.  Despite its signature here,
	 * this method takes a variety of parameters.
	 * 
	 * Overridden for query counting and logging.
	 * 
	 * @see        http://php.net/manual/en/pdo.query.php for a description of the possible parameters.
	 * @return     PDOStatement
	 */
	public function query()
	{
		$debug	= $this->getDebugSnapshot();
		$args	= func_get_args();
		$return	= call_user_func_array(array($this, 'parent::query'), $args);
		
		$sql = $args[0];
		$this->log($sql, null, __METHOD__, $debug);
		$this->setLastExecutedQuery($sql);
		$this->incrementQueryCount();
		
		return $return;
	}

	/**
	 * Sets the logging level to use for logging method calls and SQL statements.
	 *
	 * @param      int $level Value of one of the Propel::LOG_* class constants.
	 */
	public function setLogLevel($level)
	{
		$this->logLevel = $level;
	}

	/**
	 * Sets a logger to use.
	 * 
	 * The logger will be used by this class to log various method calls and their properties.
	 * 
	 * @param      BasicLogger $logger A Logger with an API compatible with BasicLogger (or PEAR Log).
	 */
	public function setLogger($logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Logs the method call or SQL using the Propel::log() method or a registered logger class.
	 * 
	 * @uses       self::getLogPrefix()
	 * @see        self::setLogger()
	 * 
	 * @param      string $msg Message to log.
	 * @param      int $level (optional) Log level to use; will use self::setLogLevel() specified level by default.
	 * @param      string $methodName (optional) Name of the method whose execution is being logged.
	 * @param      array $debugSnapshot (optional) Previous return value from self::getDebugSnapshot().
	 */
	public function log($msg, $level = null, $methodName = null, array $debugSnapshot = null)
	{
		// If logging has been specifically disabled, this method won't do anything
		if (!$this->getLoggingConfig('enabled', true))
			return;
		
		// If the method being logged isn't one of the ones to be logged, bail
		if (!in_array($methodName, $this->getLoggingConfig('methods', self::$defaultLogMethods)))
			return;
		
		// If a logging level wasn't provided, use the default one
		if ($level === null)
			$level = $this->logLevel;

        // Determine if this query is slow enough to warrant logging
        if ($this->getLoggingConfig("onlyslow", self::DEFAULT_ONLYSLOW_ENABLED))
        {
            $now = $this->getDebugSnapshot();
            if ($now['microtime'] - $debugSnapshot['microtime'] < $this->getLoggingConfig("details.slow.threshold", self::DEFAULT_SLOW_THRESHOLD)) return;
        }
		
		// If the necessary additional parameters were given, get the debug log prefix for the log line
		if ($methodName && $debugSnapshot)
			$msg = $this->getLogPrefix($methodName, $debugSnapshot) . $msg;
		
		// We won't log empty messages
		if (!$msg)
			return;
		
		// Delegate the actual logging forward
		if ($this->logger)
			$this->logger->log($msg, $level);
		else
			Propel::log($msg, $level);
	}
	
	/**
	 * Returns a snapshot of the current values of some functions useful in debugging.
	 *
	 * @return     array
	 */
	public function getDebugSnapshot()
	{
		return array(
			'microtime'				=> microtime(true),
			'memory_get_usage'		=> memory_get_usage($this->getLoggingConfig('realmemoryusage', false)),
			'memory_get_peak_usage'	=> memory_get_peak_usage($this->getLoggingConfig('realmemoryusage', false)),
			);
	}
	
	/**
	 * Returns a named configuration item from the Propel runtime configuration, from under the
	 * 'debugpdo.logging' prefix.  If such a configuration setting hasn't been set, the given default
	 * value will be returned. 
	 *
	 * @param      string $key Key for which to return the value.
	 * @param      mixed $defaultValue Default value to apply if config item hasn't been set.
	 * @return     mixed
	 */
	protected function getLoggingConfig($key, $defaultValue)
	{
		return Propel::getConfiguration(PropelConfiguration::TYPE_OBJECT)->getParameter("debugpdo.logging.$key", $defaultValue);
	}
	
	/**
	 * Returns a prefix that may be prepended to a log line, containing debug information according
	 * to the current configuration.
	 * 
	 * Uses a given $debugSnapshot to calculate how much time has passed since the call to self::getDebugSnapshot(),
	 * how much the memory consumption by PHP has changed etc.
	 *
	 * @see        self::getDebugSnapshot()
	 * 
	 * @param      string $methodName Name of the method whose execution is being logged.
	 * @param      array $debugSnapshot A previous return value from self::getDebugSnapshot().
	 * @return     string
	 */
	protected function getLogPrefix($methodName, $debugSnapshot)
	{
		$prefix		= '';
		$now		= $this->getDebugSnapshot();
		$logDetails	= array_keys($this->getLoggingConfig('details', array()));
		$innerGlue	= $this->getLoggingConfig('innerglue', ': ');
		$outerGlue	= $this->getLoggingConfig('outerglue', ' | ');
		
		// Iterate through each detail that has been configured to be enabled
		foreach ($logDetails as $detailName) {
			
			if (!$this->getLoggingConfig("details.$detailName.enabled", false))
				continue;
			
			switch ($detailName) {
				
				case 'slow';
					$value = $now['microtime'] - $debugSnapshot['microtime'] >= $this->getLoggingConfig("details.$detailName.threshold", self::DEFAULT_SLOW_THRESHOLD) ? 'YES' : ' NO';
					break;
				
				case 'time':
					$value = number_format($now['microtime'] - $debugSnapshot['microtime'], $this->getLoggingConfig("details.$detailName.precision", 3)) . ' sec';
					$value = str_pad($value, $this->getLoggingConfig("details.$detailName.pad", 10), ' ', STR_PAD_LEFT);
					break;
				
				case 'mem':
					$value = self::getReadableBytes($now['memory_get_usage'], $this->getLoggingConfig("details.$detailName.precision", 1));
					$value = str_pad($value, $this->getLoggingConfig("details.$detailName.pad", 9), ' ', STR_PAD_LEFT);
					break;
				
				case 'memdelta':
					$value = $now['memory_get_usage'] - $debugSnapshot['memory_get_usage'];
					$value = ($value > 0 ? '+' : '') . self::getReadableBytes($value, $this->getLoggingConfig("details.$detailName.precision", 1));
					$value = str_pad($value, $this->getLoggingConfig("details.$detailName.pad", 10), ' ', STR_PAD_LEFT);
					break;
				
				case 'mempeak':
					$value = self::getReadableBytes($now['memory_get_peak_usage'], $this->getLoggingConfig("details.$detailName.precision", 1));
					$value = str_pad($value, $this->getLoggingConfig("details.$detailName.pad", 9), ' ', STR_PAD_LEFT);
					break;
				
				case 'querycount':
					$value = $this->getQueryCount();
					$value = str_pad($value, $this->getLoggingConfig("details.$detailName.pad", 2), ' ', STR_PAD_LEFT);
					break;
				
				case 'method':
					$value = $methodName;
					$value = str_pad($value, $this->getLoggingConfig("details.$detailName.pad", 28), ' ', STR_PAD_RIGHT);
					break;
				
				default:
					$value = 'n/a';
					break;
				
			}
			
			$prefix .= $detailName . $innerGlue . $value . $outerGlue;
			
		}
		
		return $prefix;
	}
	
	/**
	 * Returns a human-readable representation of the given byte count.
	 *
	 * @param      int $bytes Byte count to convert.
	 * @param      int $precision How many decimals to include.
	 * @return     string
	 */
	protected function getReadableBytes($bytes, $precision)
	{
		$suffix	= array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	    $total	= count($suffix);
	    
	    for ($i = 0; $bytes > 1024 && $i < $total; $i++)
	    	$bytes /= 1024;
	    
	    return number_format($bytes, $precision) . ' ' . $suffix[$i];
	}
	
	/**
	 * If so configured, makes an entry to the log of the state of this DebugPDO instance just prior to its destruction.
	 *
	 * @see        self::log()
	 */
	public function __destruct()
	{
		$this->log('', null, __METHOD__, $this->getDebugSnapshot());
	}

}
