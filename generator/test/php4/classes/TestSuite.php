<?php
/*
 * $Id: TestSuite.php,v 1.1 2004/11/29 16:01:20 micha Exp $
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

require_once 'PHPUnit/TestSuite.php';

class TestSuite extends PHPUnit_TestSuite
{

  function TestSuite($name)
  {
    $line = "--------------------------------------------";
    $info = "| Running test suite '$name'";
    $info = $info . str_repeat(' ', strlen($line) - strlen($info) - 1) . '|';

    print "\n";
    print "$line\n";
    print "$info\n";
    print "$line\n";        
    print "\n";

    parent::PHPUnit_TestSuite($name);
  }

  /**
  * Add a test suite.
  *
  * @param  object
  */
  function addTestSuite($name)
  {
    print "[INFO] Adding test suite '$name'\n";
    parent::addTestSuite($name);
  }

  /**
  *
  */
  function run(&$result)
  {
    print "\n";
    parent::run($result);
  }

}

