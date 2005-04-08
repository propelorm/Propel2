<?php

    define('PROPEL_TEST_BASE', dirname(__FILE__));
    define('PHPUnit2_MAIN_METHOD', "don't let PHPUnit try to auto-invoke anything!");
    ini_set('include_path',  dirname(__FILE__). PATH_SEPARATOR . ini_get('include_path'));
    
    include_once 'PHPUnit2/TextUI/TestRunner.php';
    include_once 'PHPUnit2/Framework/TestSuite.php';
    
        
    // Query-builder tools    
    $utilSuite = new PHPUnit2_Framework_TestSuite("Query Builders");
    
    require_once 'propel/util/CriteriaTest.php';
    $utilSuite->addTestSuite(new ReflectionClass('CriteriaTest'));
    
    
    // Engine tests
    // ------------
    // Need to load phing here
    require_once 'phing/Phing.php';
    Phing::startup();
    
    // Require the
    $modelSuite = new PHPUnit2_Framework_TestSuite("Model");
    
    require_once 'propel/engine/database/model/TableTest.php';
    $modelSuite->addTestSuite(new ReflectionClass('TableTest'));
    
    require_once 'propel/engine/database/model/NameFactoryTest.php';
    $modelSuite->addTestSuite(new ReflectionClass('NameFactoryTest'));
    
    
    // Main suite
    $suite = new PHPUnit2_Framework_TestSuite('Propel Tests');

    $suite->addTest($utilSuite);        
    $suite->addTest($modelSuite);
    
    // Run it!
    PHPUnit2_TextUI_TestRunner::run($suite);    

    Phing::shutdown();
?>