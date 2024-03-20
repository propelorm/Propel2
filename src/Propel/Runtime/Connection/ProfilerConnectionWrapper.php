<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Propel;
use Propel\Runtime\Util\Profiler;

/**
 * Connection class with profiling abilities.
 */
class ProfilerConnectionWrapper extends ConnectionWrapper
{
    /**
     * Whether the debug is enabled
     *
     * @var bool
     */
    protected $useDebugModeOnInstance = true;

    /**
     * @var \Propel\Runtime\Util\Profiler
     */
    protected $profiler;

    /**
     * @var bool
     * Whether the logging is enabled only for slow queries.
     * The slow threshold is set on the profiler.
     */
    protected $isSlowOnly = false;

    /**
     * @param \Propel\Runtime\Util\Profiler $profiler
     *
     * @return void
     */
    public function setProfiler(Profiler $profiler): void
    {
        $this->profiler = $profiler;
    }

    /**
     * @return \Propel\Runtime\Util\Profiler
     */
    public function getProfiler(): Profiler
    {
        if ($this->profiler === null) {
            $this->profiler = Propel::getServiceContainer()->getProfiler();
        }

        return $this->profiler;
    }

    /**
     * Overrides the parent setAttribute to support the isSlowOnly attribute.
     *
     * @param string|int $attribute The attribute name, or the constant name containing the attribute name (e.g. 'PDO::ATTR_CASE')
     * @param mixed $value
     *
     * @return bool
     */
    public function setAttribute($attribute, $value): bool
    {
        switch ($attribute) {
            case 'isSlowOnly':
                // Set whether the connection must only log slow queries.
                // The slow threshold must be set on the profiler (100ms by default).
                $this->isSlowOnly = $value;

                return true;
            default:
                return parent::setAttribute($attribute, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function prepare(string $statement, array $driverOptions = [])
    {
        $this->getProfiler()->start();

        return parent::prepare($statement, $driverOptions);
    }

    /**
     * @inheritDoc
     */
    public function exec($statement): int
    {
        $this->getProfiler()->start();

        return parent::exec($statement);
    }

    /**
     * @inheritDoc
     */
    public function query($statement = '', ...$args): DataFetcherInterface
    {
        $this->getProfiler()->start();

        return parent::query($statement, ...$args);
    }

    /**
     * @inheritDoc
     */
    protected function createStatementWrapper($sql): StatementWrapper
    {
        return new ProfilerStatementWrapper($sql, $this);
    }

    /**
     * @inheritDoc
     */
    public function log($msg): void
    {
        if ($this->isSlowOnly && !$this->getProfiler()->isSlow()) {
            return;
        }
        $msg = $this->getProfiler()->getProfile() . $msg;

        parent::log($msg);
    }
}
