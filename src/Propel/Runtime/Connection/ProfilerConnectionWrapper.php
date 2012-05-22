<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Propel;
use Propel\Runtime\Util\Profiler;

/**
 * Connection class with profiling abilities.
 */
class ProfilerConnectionWrapper extends ConnectionWrapper
{
    /**
     * Whether or not the debug is enabled
     *
     * @var Boolean
     */
    public $useDebug = true;

    /**
     * @var \Propel\Runtime\Util\Profiler
     */
    protected $profiler;

    /**
     * Whether the logging is enabled only for slow queries.
     * The slow treshold is set on the profiler.
     */
    protected $isSlowOnly = false;

    /**
     * @param \Propel\Runtime\Util\Profiler $profiler
     */
    public function setProfiler(Profiler $profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * @return \Propel\Runtime\Util\Profiler
     */
    public function getProfiler()
    {
        if (null === $this->profiler) {
            $this->profiler = Propel::getServiceContainer()->getProfiler();
        }

        return $this->profiler;
    }

    /**
     * Overrides the parent setAttribute to support the isSlowOnly attribute.
     */
    public function setAttribute($attribute, $value)
    {
        switch ($attribute) {
            case 'isSlowOnly':
                // Set whether the connection must only log slow queries.
                // The slow treshold must be set on the profiler (100ms by default).
                $this->isSlowOnly = $value;
                break;
            default:
                parent::setAttribute($attribute, $value);
        }
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * Overrides PDO::prepare() in order to:
     *  - Add logging and query counting if logging is true.
     *  - Add query caching support if the PropelPDO::PROPEL_ATTR_CACHE_PREPARES was set to true.
     *
     * @param string $sql            This must be a valid SQL statement for the target database server.
     * @param array  $driver_options One $array or more key => value pairs to set attribute values
     *                                      for the PDOStatement object that this method returns.
     *
     * @return \Propel\Runtime\Connection\StatementInterface
     */
    public function prepare($sql, $driver_options = array())
    {
        $this->getProfiler()->start();

        if ($this->isCachePreparedStatements) {
            if (!isset($this->cachedPreparedStatements[$sql])) {
                $return = new ProfilerStatementWrapper($sql, $this, $driver_options);
                $this->cachedPreparedStatements[$sql] = $return;
            } else {
                $return = $this->cachedPreparedStatements[$sql];
            }
        } else {
            $return = new ProfilerStatementWrapper($sql, $this, $driver_options);
        }

        if ($this->useDebug) {
            $this->log($sql, null, 'prepare');
        }

        return $return;
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     * Overrides PDO::exec() to log queries when required
     *
     * @param  string  $sql
     * @return integer
     */
    public function exec($sql)
    {
        $this->getProfiler()->start();

        return parent::exec($sql);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     * Despite its signature here, this method takes a variety of parameters.
     *
     * Overrides PDO::query() to log queries when required
     *
     * @see http://php.net/manual/en/pdo.query.php for a description of the possible parameters.
     *
     * @return PDOStatement
     */
    public function query()
    {
        $this->getProfiler()->start();
        $args = func_get_args();

        return call_user_func_array('parent::query', $args);
    }

    /**
     * Logs the method call or SQL using the Propel::log() method or a registered logger class.
     *
     * @uses      self::getLogPrefix()
     * @see self::setLogger()
     *
     * @param string  $msg           Message to log.
     * @param integer $level         Log level to use; will use self::setLogLevel() specified level by default.
     * @param string  $methodName    Name of the method whose execution is being logged.
     * @param array   $debugSnapshot Previous return value from self::getDebugSnapshot().
     */
    public function log($msg, $level = null, $methodName = null)
    {
        if ($this->isSlowOnly && !$this->getProfiler()->isSlow()) {
            return;
        }
        $msg = $this->getProfiler()->getProfile() . $msg;

        return parent::log($msg, $level, $methodName);
    }

}
