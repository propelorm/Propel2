<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Helpers;
use Propel\Tests\TestCase;

/**
 * Base functionality to be extended by all Propel test cases.  Test
 * case implementations are used to automate unit testing via PHPUnit.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Christopher Elkins <celkins@scardini.com> (Torque)
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * Conditional compilation flag.
     */
    const DEBUG = false;
}
