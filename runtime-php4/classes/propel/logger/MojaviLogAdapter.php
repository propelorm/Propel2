<?php
/*
 *  $Id: MojaviLogAdapter.php,v 1.1 2004/08/13 16:05:07 micha Exp $
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

// include BasicLogger from include path
require_once 'propel/logger/BasicLogger.php';
 
/**
 * Mojavi logging adapter for propel
 * 
 * @author Brandon Keepers <brandon@opensoul.org>
 * @version $Revision: 1.1 $
 * @package propel.logger
 */
class MojaviLogAdapter extends BasicLogger 
{
  
  /**
  * Instance of mojavi logger
  */
  var $logger = null;

  /**
  * constructor for setting up Mojavi log adapter
  *
  * @param   ErrorLog   $logger   instance of Mojavi error log obtained by
  *                               calling LogManager::getLogger();
  */
  function MojaviLogAdapter(&$logger)
  {
    $this->logger =& $logger;
  }

  /**
  * A convenience function for logging an alert event.
  *
  * @param   mixed   $message    String or Exception object containing the message
  *                              to log.
  */
  function alert($message)
  {
    $this->log($message, 'alert');
  }

  /**
  * A convenience function for logging a critical event.
  *
  * @param   mixed   $message    String or Exception object containing the message
  *                              to log.
  */
  function crit($message)
  {
    $this->log($message, 'crit');
  }
 
  /**
  * A convenience function for logging an error event.
  *
  * @param   mixed   $message    String or Exception object containing the message
  *                              to log.
  */
  function err($message)
  {
    $this->log($message, 'err');
  }

  /**
  * A convenience function for logging a warning event.
  *
  * @param   mixed   $message    String or Exception object containing the message
  *                              to log.
  */
  function warning($message)
  {
    $this->log($message, 'warning');
  }
  
  
  /**
  * A convenience function for logging an critical event.
  *
  * @param   mixed   $message    String or Exception object containing the message
  *                              to log.
  */
  function notice($message)
  {
    $this->log($message, 'notice');
  }

  /**
  * A convenience function for logging an critical event.
  *
  * @param   mixed   $message    String or Exception object containing the message
  *                              to log.
  */
  function info($message)
  {
    $this->log($message, 'info');
  }
  
  /**
  * A convenience function for logging a debug event.
  *
  * @param   mixed   $message    String or Exception object containing the message
  *                              to log.
  */
  function debug($message)
  {
    $this->log($message, 'debug');
  }
  
  /**
  * Primary method to handle logging.
  *
  * @param   mixed   $message    String or Exception object containing the message
  *                              to log.
  * @param   int     $severity   The numeric severity.  Defaults to null so that no
  *                              assumptions are made about the logging backend.
  */
  function log($message, $severity = null)
  {
    if(is_null($this->logger)) 
    {
      $this->logger =& LogManager::getLogger('propel');
    }

    switch($severity)
    {
      case 'crit':
        $method = 'fatal';
        break;
      case 'err':
        $method = 'error';
        break;
      case 'alert':
      case 'warning':
        $method = 'warning';
        break;
      case 'notice':
      case 'info':
        $method = 'info';
        break;
      case 'debug':
      default:
        $method = 'debug';
    }
    
    // get a backtrace to pass class, function, file, & line to Mojavi logger
    $trace =& debug_backtrace();

    // call the appropriate Mojavi logger method
    $this->logger->{$method} (
        $message,
        $trace[2]['class'],
        $trace[2]['function'],
        $trace[1]['file'],
        $trace[1]['line']
    );
  }

};



