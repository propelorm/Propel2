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
     * @var boolean
     */
    public $useDebug = true;

    /**
     * @var \Propel\Runtime\Util\Profiler
     */
    protected $profiler;

    /**
     * Whether the logging is enabled only for slow queries.
     * The slow threshold is set on the profiler.
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
     *
     * @param string $attribute The attribute name, or the constant name containing the attribute name (e.g. 'PDO::ATTR_CASE')
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        switch ($attribute) {
            case 'isSlowOnly':
                // Set whether the connection must only log slow queries.
                // The slow threshold must be set on the profiler (100ms by default).
                $this->isSlowOnly = $value;
                break;
            default:
                parent::setAttribute($attribute, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($statement, $driver_options = null)
    {
        $this->getProfiler()->start();

        return parent::prepare($statement, $driver_options);
    }

    /**
     * {@inheritDoc}
     */
    public function exec($sql)
    {
        $this->getProfiler()->start();

        return parent::exec($sql);
    }

    /**
     * {@inheritDoc}
     */
    public function query($statement = '')
    {
        $this->getProfiler()->start();
        $args = func_get_args();

        return call_user_func_array('parent::query', $args);
    }

    /**
     * {@inheritDoc}
     */
    protected function createStatementWrapper($sql)
    {
        return new ProfilerStatementWrapper($sql, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function log($msg)
    {
        if ($this->isSlowOnly && !$this->getProfiler()->isSlow()) {
            return;
        }
        $msg = $this->getProfiler()->getProfile() . $msg;

        return parent::log($msg);
    }
}
