<?php

require_once 'propel/Propel.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Base functionality to be extended by all Propel test cases.  Test
 * case implementations are used to automate unit testing via PHPUnit.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author     Christopher Elkins <celkins@scardini.com> (Torque)
 * @version    $Revision$
 */
abstract class BaseTestCase extends PHPUnit_Framework_TestCase {

	/**
	 * Conditional compilation flag.
	 */
	const DEBUG = false;

}
