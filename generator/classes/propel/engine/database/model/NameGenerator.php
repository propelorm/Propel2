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
 * The generic interface to a name generation algorithm.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author     Byron Foster <byron_foster@yahoo.com> (Torque)
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
interface NameGenerator {
	/**
	 * The character used by most implementations as the separator
	 * between name elements.
	 */
	const STD_SEPARATOR_CHAR = '_';

	/**
	 * Traditional method for converting schema table and column names
	 * to PHP names.  The <code>CONV_METHOD_XXX</code> constants
	 * define how names for columns and tables in the database schema
	 * will be converted to PHP source names.
	 *
	 * @see        PhpNameGenerator::underscoreMethod()
	 */
	const CONV_METHOD_UNDERSCORE = "underscore";

	/**
	 * Similar to {@link #CONV_METHOD_UNDERSCORE} except nothing is
	 * converted to lowercase.
	 *
	 * @see        PhpNameGenerator::phpnameMethod()
	 */
	const CONV_METHOD_PHPNAME = "phpname";

	/**
	 * Specifies no modification when converting from a schema column
	 * or table name to a PHP name.
	 */
	const CONV_METHOD_NOCHANGE = "nochange";

	/**
	 * Given a list of <code>String</code> objects, implements an
	 * algorithm which produces a name.
	 *
	 * @param      inputs Inputs used to generate a name.
	 * @return     The generated name.
	 * @throws     EngineException
	 */
	public function generateName($inputs);
}
