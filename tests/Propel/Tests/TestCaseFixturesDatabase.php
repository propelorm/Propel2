<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use Propel\Generator\Command\TestPrepareCommand;
use Propel\Runtime\Propel;
use Symfony\Component\Console\Application;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\Finder\Finder;

/**
 * The same as TestCaseFixtures but makes additional sure that
 * database schema has been updated.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCaseFixturesDatabase extends TestCaseFixtures
{
    protected static $withDatabaseSchema = true;
}
