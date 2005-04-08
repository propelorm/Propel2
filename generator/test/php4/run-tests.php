<?php
/*
 * $Id: run-tests.php,v 1.4 2005/03/19 13:37:54 micha Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */ 

define('ROOT',             realpath(dirname(__FILE__) . '/../../../../') . '/');
define('CREOLE_BASE',      ROOT . 'creole/');
define('PROPEL_BASE',      ROOT . 'propel/');
define('PROPEL_TEST_BASE', PROPEL_BASE . 'propel-generator/test/');
define('BOOKSTORE_DIR',    PROPEL_BASE . 'propel-generator/projects/bookstore/');
define('BOOKSTORE_CONF',   BOOKSTORE_DIR . 'build/conf/bookstore-conf.php');

$includes   = array();
$includes[] = CREOLE_BASE . 'creole-php4/classes/';
$includes[] = PROPEL_BASE . 'propel-php4/classes/';
$includes[] = PROPEL_TEST_BASE . 'php4/';
$includes[] = PROPEL_TEST_BASE . 'php4/classes';
$includes[] = BOOKSTORE_DIR . 'build/classes';
$includes[] = ini_get('include_path');

// don't forget to add current include_path
ini_set('include_path', implode(PATH_SEPARATOR, $includes));

require_once 'PHPUnit.php';
require_once 'TestSuite.php';
require_once 'TestListener.php';
require_once 'Benchmark/Timer.php';
require_once 'propel/Propel.php';
require_once 'propel/util/CriteriaTest.php';
require_once 'propel/validator/ValidatorTest.php';
require_once 'propel/GeneratedObjectTest.php';
require_once 'propel/GeneratedPeerTest.php';

// ----------------------------------------------------------------------------
// TESTS ----------------------------------------------------------------------
// ----------------------------------------------------------------------------

$timer = new Benchmark_Timer();    
$timer->start();  

$result = new PHPUnit_TestResult();
$result->addListener(new TestListener());

// Query-builder tools
$utilSuite = new TestSuite("Query Builders");
$utilSuite->addTestSuite('CriteriaTest');

$utilSuite->run($result);
$timer->setMarker('Query Builders');

$omSuite = new TestSuite('OM Tests');
$omSuite->addTestSuite('GeneratedObjectTest');
$omSuite->addTestSuite('GeneratedPeerTest');
$omSuite->addTestSuite('ValidatorTest');

/* suppress notice of exceptions */
error_reporting(E_ALL ^ E_USER_NOTICE);

$omSuite->run($result);
$timer->setMarker('OM Tests');

$timer->stop();
$timer->display();
