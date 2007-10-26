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

include_once 'propel/engine/database/model/NameGenerator.php';

/**
 * A <code>NameGenerator</code> implementation for PHP-esque names.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author     Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author     Bernd Goldschmidt <bgoldschmidt@rapidsoft.de>
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class PhpNameGenerator implements NameGenerator {

	/**
	 * <code>inputs</code> should consist of two (three) elements, the
	 * original name of the database element and the method for
	 * generating the name.
	 * The optional third element may contain a prefix that will be
	 * stript from name prior to generate the resulting name.
	 * There are currently three methods:
	 * <code>CONV_METHOD_NOCHANGE</code> - xml names are converted
	 * directly to php names without modification.
	 * <code>CONV_METHOD_UNDERSCORE</code> will capitalize the first
	 * letter, remove underscores, and capitalize each letter before
	 * an underscore.  All other letters are lowercased. "phpname"
	 * works the same as the <code>CONV_METHOD_PHPNAME</code> method
	 * but will not lowercase any characters.
	 *
	 * @param      inputs list expected to contain two (optional: three) parameters,
	 * element 0 contains name to convert, element 1 contains method for conversion,
	 * optional element 2 contains prefix to be striped from name
	 * @return     The generated name.
	 * @see        NameGenerator
	 */
	public function generateName($inputs)
	{
		$schemaName = $inputs[0];
		$method = $inputs[1];

		if (count($inputs)>2) {
			$prefix = $inputs[2];
			if ($prefix != '' && substr($schemaName, 0, strlen($prefix)) == $prefix) {
				$schemaName = substr($schemaName, strlen($prefix));
			}
		}

		$phpName = null;

		if ($method == self::CONV_METHOD_UNDERSCORE) {
			$phpName = $this->underscoreMethod($schemaName);
		} elseif ($method == self::CONV_METHOD_PHPNAME) {
			$phpName = $this->phpnameMethod($schemaName);
		} else if ($method == self::CONV_METHOD_NOCHANGE) {
			$phpName = $this->nochangeMethod($schemaName);
		} else {
			// if for some reason nothing is defined then we default
			// to the traditional method.
			$phpName = $this->underscoreMethod($schemaName);
		}

		return $phpName;
	}

	/**
	 * Converts a database schema name to php object name.  Removes
	 * <code>STD_SEPARATOR_CHAR</code>, capitilizes first letter of
	 * name and each letter after the <code>STD_SEPERATOR</code>,
	 * converts the rest of the letters to lowercase.
	 *
	 * my_CLASS_name -> MyClassName
	 *
	 * @param      string $schemaName name to be converted.
	 * @return     string Converted name.
	 * @see        NameGenerator
	 * @see        #underscoreMethod()
	 */
	protected function underscoreMethod($schemaName)
	{
		$name = "";
		$tok = strtok($schemaName, self::STD_SEPARATOR_CHAR);
		while ($tok) {
			$name .= ucfirst(strtolower($tok));
			$tok = strtok(self::STD_SEPARATOR_CHAR);
		}
		return $name;
	}

	/**
	 * Converts a database schema name to php object name.  Operates
	 * same as underscoreMethod but does not convert anything to
	 * lowercase.
	 *
	 * my_CLASS_name -> MyCLASSName
	 *
	 * @param      string $schemaName name to be converted.
	 * @return     string Converted name.
	 * @see        NameGenerator
	 * @see        #underscoreMethod(String)
	 */
	protected function phpnameMethod($schemaName)
	{
		$name = "";
		$tok = strtok($schemaName, self::STD_SEPARATOR_CHAR);
		while ($tok) {
			$name .= ucfirst($tok);
			$tok = strtok(self::STD_SEPARATOR_CHAR);
		}
		return $name;
	}

	/**
	 * Converts a database schema name to PHP object name.  In this
	 * case no conversion is made.
	 *
	 * @param      string $name name to be converted.
	 * @return     string The <code>name</code> parameter, unchanged.
	 */
	protected function nochangeMethod($name)
	{
		return $name;
	}
}
