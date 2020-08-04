<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests;

/**
 * The same as TestCaseFixtures but makes additional sure that
 * database schema has been updated.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCaseFixturesDatabase extends TestCaseFixtures
{
    /**
     * @var bool
     */
    protected static $withDatabaseSchema = true;
}
