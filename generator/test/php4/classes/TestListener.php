<?php
/*
 * $Id: TestListener.php,v 1.2 2004/11/29 16:02:25 micha Exp $
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

require_once 'PHPUnit/TestListener.php';

class TestListener extends PHPUnit_TestListener
{
  /**
  * Test cache.
  * @var array
  */
  var $cache = array();
  /**
  * Current test class.
  * @var string
  */
  var $class = '';
  /**
  * Current test name.
  * @var string
  */
  var $name  = '';


  /**
  * A test started.
  *
  * @param  object
  */
  function startTest(&$test)
  {
    $this->class = get_class($test);
    $this->name = $test->getName();

    print "[{$this->class}]: {$this->name}() ";
  }

  /**
  * An error occurred.
  *
  * @param  object
  * @param  object
  */
  function addError(&$test, &$t)
  {
    $class = get_class($test);
    $name = $test->getName();

    if (! isset($this->cache[$class][$name])) {
      $this->cache[$class][$name] = 1;
      print "[ ERROR ]\n\n";
    }

    print "==> $t\n\n";
  }

  /**
  * A failure occurred.
  *
  * @param  object
  * @param  object
  */
  function addFailure(&$test, &$t)
  {
    $class = get_class($test);
    $name = $test->getName();

    if (! isset($this->cache[$class][$name])) {
      $this->cache[$class][$name] = 1;
      print "[ FAILED ]\n\n";
    }

    print "==> $t\n\n";
  }

  /**
  * An error occurred.
  *
  * @param  object
  * @param  object
  * @access public
  * @abstract
  */
  function endTest(&$test)
  {
    $class = get_class($test);
    $name = $test->getName();

    if (isset($this->cache[$class][$name]))
    {
      exit();
    }

    print "[ PASSED ]\n";
  }

}

