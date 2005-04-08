<?php

    define('PROPEL_TEST_BASE', dirname(__FILE__));
    define('PHPUnit2_MAIN_METHOD', "don't let PHPUnit try to auto-invoke anything!");
	
	// temporary, until phing task puts this in correct location
	define('BOOKSTORE_CONF', PROPEL_TEST_BASE . '/../projects/bookstore/build/conf/bookstore-conf.php');
	
	$include_paths = array();
	$include_paths[] = dirname(__FILE__);
	$include_paths[] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes';
	
	// temporary, until PHPUnit2 Phing support added
	$include_paths[] = dirname(__FILE__) . '/../projects/bookstore/build/classes';
	
	// don't forget to add current include_path
	$include_paths[] = get_include_path();
	
	set_include_path(implode(PATH_SEPARATOR, $include_paths));
    
    require_once 'PHPUnit2/TextUI/TestRunner.php';
    require_once 'PHPUnit2/Framework/TestSuite.php';
    
	require_once 'propel/Propel.php';
	Propel::init(BOOKSTORE_CONF);
	
  require_once 'propel/GeneratedObjectTest.php';
	require_once 'propel/GeneratedPeerTest.php';
  require_once 'propel/validator/ValidatorTest.php';
	
	$suite = new PHPUnit2_Framework_TestSuite('OM Tests');	
	$suite->addTestSuite(new ReflectionClass('GeneratedObjectTest'));
  $suite->addTestSuite(new ReflectionClass('GeneratedPeerTest'));
  $suite->addTestSuite(new ReflectionClass('ValidatorTest'));
    
    // Run it!
    PHPUnit2_TextUI_TestRunner::run($suite);

?>
