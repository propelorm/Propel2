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
 * DateTime subclass to support serialization.
 *
 * @author     Alan Pinstein
 * @author     Soenke Ruempler
 * @package    propel.util
 */
class PropelDateTime extends DateTime
{
	
	/**
	 * A string representation of the date, for serialization.
	 * @var string
	 */
	private $dateString;
	
	/**
	 * PHP "magic" function called when object is serialized.
	 * Sets an internal property with the date string and returns properties
	 * of class that should be serialized.
	 * @return array string[]
	 */
	function __sleep()
	{
		// Make serialization work as expected. 
	    $this->dateString = $this->format('r');
	    return array('dateString');
	}
	
	/**
	 * PHP "magic" function called when object is restored from serialized state.
	 * Calls DateTime constructor with previously stored string value of date. 
	 */
	function __wakeup()
	{
	    parent::__construct($this->dateString);
	}

}