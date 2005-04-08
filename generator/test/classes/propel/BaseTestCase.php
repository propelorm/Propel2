<?php

require_once 'propel/Propel.php';
require_once 'PHPUnit2/Framework/TestCase.php';

/**
 * Base functionality to be extended by all Propel test cases.  Test
 * case implementations are used to automate unit testing via PHPUnit.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Christopher Elkins <celkins@scardini.com> (Torque)
 * @version $Revision: 1.1 $
 */
abstract class BaseTestCase extends PHPUnit2_Framework_TestCase {

    /**
     * Conditional compilation flag.
     */
    const DEBUG = false;

    /**
     * The path to the configuration file.
     */
    const CONFIG_FILE = "propel-conf.php";

    /**
     * Whether propel has been initialized.
     */
    private static $hasInitialized = false;   

    /**
     * Initialize Propel on the first setUp().  Subclasses which
     * override setUp() must call super.setUp() as their first action.
     * 
     * @return void
     */
    public function setUp()
    {
        if (!self::$hasInitialized) {
            try {
                Propel::init(self::CONFIG_FILE);
                self::$hasInitialized = true;
            } catch (Exception $e) {
                    $this->fail("Couldn't initialize Propel: " . $e->getMessage());
            }
        }
        
    }
}
