<?php
/*
 * $Id: BaseTestCase.php,v 1.1 2004/11/20 17:48:26 micha Exp $
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

require_once 'propel/Propel.php';
require_once 'PHPUnit/TestCase.php';

/**
 * Base functionality to be extended by all Propel test cases.  Test
 * case implementations are used to automate unit testing via PHPUnit.
 *
 * @author Michael Aichler <aichler@mediacluster.de> (Propel)
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Christopher Elkins <celkins@scardini.com> (Torque)
 * @version $Revision: 1.1 $
 */
class BaseTestCase extends PHPUnit_TestCase
{

  /**
  * Conditional compilation flag.
  */
  function DEBUG() { return false; }

  /**
  * The path to the configuration file.
  */
  function CONFIG_FILE() { return BOOKSTORE_CONF; }


  /**
  * BaseTestCase constructor.
  */
  function BaseTestCase($name)
  {
    parent::PHPUnit_TestCase($name);
  }

  /**
  * Initialize Propel on the first setUp().  Subclasses which
  * override setUp() must call super.setUp() as their first action.
  *
  * @return void
  */
  function setUp()
  {
    static $hasInitialized = false;

    if (! $hasInitialized)
    {
      $e = Propel::init(BaseTestCase::CONFIG_FILE());
      $hasInitialized = true;

      if (Propel::isError($e)) {
        $this->fail("Couldn't initialize Propel: " . $e->getMessage());
      }
    }
  }

}
