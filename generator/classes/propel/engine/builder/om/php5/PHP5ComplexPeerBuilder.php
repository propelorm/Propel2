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

require_once 'propel/engine/builder/om/php5/PHP5BasicPeerBuilder.php';

/**
 * Generates a PHP5 base Peer class with complex object model methods.
 * 
 * This class extends the basic peer builder by adding on the doSelectJoin*()
 * methods and other complex object model methods.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.om.php5
 */
class PHP5ComplexPeerBuilder extends PHP5BasicPeerBuilder {		

	/**
	 * Adds the complex OM methods to the base addSelectMethods() function.
	 * @param string &$script The script will be modified in this method.
	 * @see PeerBuilder::addSelectMethods()
	 */
	protected function addSelectMethods(&$script)
	{
		parent::addSelectMethods($script);
		
	}
		
} // PHP5ComplexPeerBuilder
