<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Service class for parsing a large SQL string into an array of SQL statements
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.util
 */
class PropelSQLParser
{
	protected $delimiter = ';';
	
	protected $sql = '';
	protected $len = 0;
	protected $pos = 0;
	
	/**
	 * Sets the inner SQL string for this object.
	 * Also resets the parsing cursor (see getNextStatement)
	 *
	 * @param string $sql The SQL string to parse
	 */
	public function setSQL($sql)
	{
		$this->sql = $sql;
		$this->pos = 0;
		$this->len = strlen($sql);
	}
	
	/**
	 * Gets the inner SQL string for this object.
	 *
	 * @return string The SQL string to parse
	 */
	public function getSQL()
	{
		return $this->sql;
	}
	
	/**
	 * Explodes a SQL string into an array of SQL statements.
	 * @example
	 * <code>
	 * echo PropelSQLParser::parseString("-- Table foo
	 * DROP TABLE foo;
	 * CREATE TABLE foo (
	 *   id int(11) NOT NULL AUTO_INCREMENT,
	 *   title varchar(255) NOT NULL,
	 *   PRIMARY KEY (id),
	 * ) ENGINE=InnoDB;");
	 * // results in
	 * // array(
	 * //   "DROP TABLE foo;",
	 * //   "CREATE TABLE foo (
	 * //      id int(11) NOT NULL AUTO_INCREMENT,
	 * //      title varchar(255) NOT NULL,
	 * //      PRIMARY KEY (id),
	 * //   ) ENGINE=InnoDB;"
	 * // )
	 * </code>
	 * @param string $input The SQL code to parse
	 *
	 * @return array A list of SQL statement strings
	 */
	public static function parseString($input)
	{
		$parser = new self();
		$parser->setSQL($input);
		$parser->convertLineFeedsToUnixStyle();
		$parser->stripSQLCommentLines();
		
		return $parser->explodeIntoStatements();
	}
	
	/**
	 * Explodes a SQL file into an array of SQL statements.
	 * @example
	 * <code>
	 * echo PropelSQLParser::parseFile('/var/tmp/foo.sql');
	 * // results in
	 * // array(
	 * //   "DROP TABLE foo;",
	 * //   "CREATE TABLE foo (
	 * //      id int(11) NOT NULL AUTO_INCREMENT,
	 * //      title varchar(255) NOT NULL,
	 * //      PRIMARY KEY (id),
	 * //   ) ENGINE=InnoDB;"
	 * // )
	 * </code>
	 * @param string $input The absolute path to the file to parse
	 *
	 * @return array A list of SQL statement strings
	 */
	public static function parseFile($file)
	{
		if (!file_exists($file)) {
			return array();
		}
		return self::parseString(file_get_contents($file));
	}
	
	public function convertLineFeedsToUnixStyle()
	{
		$this->setSQL(str_replace(array("\r\n", "\r"), "\n", $this->sql));
	}
	
	public function stripSQLCommentLines()
	{
		$this->setSQL(preg_replace(array(
			'#^\s*(//|--|\#).*(\n|$)#m',    // //, --, or # style comments
			'#^\s*/\*.*?\*/#s'              // c-style comments
		), '', $this->sql));
	}

	/**
	 * Explodes the inner SQL string into statements based on the SQL statement delimiter (;)
	 *
	 * @return array A list of SQL statement strings
	 */
	public function explodeIntoStatements()
	{
		$this->pos = 0;
		$sqlStatements = array();
		while ($sqlStatement = $this->getNextStatement()) {
			$sqlStatements[] = $sqlStatement;
		}
		
		return $sqlStatements;
	}
	
	/**
	 * Gets the next SQL statement in the inner SQL string,
	 * and advances the cursor to the end of this statement.
	 *
	 * @return string A SQL statement
	 */
	public function getNextStatement()
	{
		$isAfterBackslash = false;
		$isInString = false;
		$stringQuotes = '';
		$parsedString = '';
		while ($this->pos < $this->len) {
			$char = $this->sql[$this->pos];
			
			// check flags for strings or escaper
			switch ($char) {
				case "\\":
					$isAfterBackslash = true;
					break;
				case "'":
				case "\"":
					if ($isInString && $stringQuotes == $char) {
						if (!$isAfterBackslash) {
							$isInString = false;
						}
					} elseif (!$isInString) {
						$stringQuotes = $char;
						$isInString = true;
					}
					break;
			}

			$parsedString .= $char;
			$this->pos++;
			
			if ($char !== "\\") {
				$isAfterBackslash = false;
			}

			// check for end of statement
			if (!$isInString && $char == $this->delimiter) {
				return trim($parsedString);
			}
		}
		
		return trim($parsedString);
	}
	
}
