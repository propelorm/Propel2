<?php

/*
 *  $Id$
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


/**
 * This is a minimalistic interface that any logging class must implement for Propel.
 *
 * The API for this interface is based on the PEAR::Log interface.  It provides a simple
 * API that can be used by Propel independently of Log backend.
 *
 * PEAR::Log and perhaps the Log API was developed by Chuck Hagenbuch <chuck@horde.org>
 * and Jon Parise <jon@php.net>.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.logger
 */
interface BasicLogger {

	/**
	 * A convenience function for logging an alert event.
	 *
	 * @param      mixed   $message    String or Exception object containing the message
	 *                              to log.
	 */
	public function alert($message);

	/**
	 * A convenience function for logging a critical event.
	 *
	 * @param      mixed   $message    String or Exception object containing the message
	 *                              to log.
	 */
	public function crit($message);

	/**
	 * A convenience function for logging an error event.
	 *
	 * @param      mixed   $message    String or Exception object containing the message
	 *                              to log.
	 */
	public function err($message);

	/**
	 * A convenience function for logging a warning event.
	 *
	 * @param      mixed   $message    String or Exception object containing the message
	 *                              to log.
	 */
	public function warning($message);
	/**
	 * A convenience function for logging an critical event.
	 *
	 * @param      mixed   $message    String or Exception object containing the message
	 *                              to log.
	 */
	public function notice($message);
	/**
	 * A convenience function for logging an critical event.
	 *
	 * @param      mixed   $message    String or Exception object containing the message
	 *                              to log.
	 */
	public function info($message);

	/**
	 * A convenience function for logging a debug event.
	 *
	 * @param      mixed   $message    String or Exception object containing the message
	 *                              to log.
	 */
	public function debug($message);

	/**
	 * Primary method to handle logging.
	 *
	 * @param      mixed   $message    String or Exception object containing the message
	 *                              to log.
	 * @param      int     $severity   The numeric severity.  Defaults to null so that no
	 *                              assumptions are made about the logging backend.
	 */
	public function log($message, $severity = null);

}
