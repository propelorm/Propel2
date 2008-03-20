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
 * This is used to connect to PostgresQL databases.
 *
 * <a href="http://www.pgsql.org">http://www.pgsql.org</a>
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Hakan Tandogan <hakan42@gmx.de> (Torque)
 * @version    $Revision$
 * @package    propel.adapter
 */
class DBPostgres extends DBAdapter {

	/**
	 * This method is used to ignore case.
	 *
	 * @param      string $in The string to transform to upper case.
	 * @return     string The upper case string.
	 */
	public function toUpperCase($in)
	{
		return "UPPER(" . $in . ")";
	}

	/**
	 * This method is used to ignore case.
	 *
	 * @param      in The string whose case to ignore.
	 * @return     The string in a case that can be ignored.
	 */
	public function ignoreCase($in)
	{
		return "UPPER(" . $in . ")";
	}

	/**
	 * Returns SQL which concatenates the second string to the first.
	 *
	 * @param      string String to concatenate.
	 * @param      string String to append.
	 * @return     string
	 */
	public function concatString($s1, $s2)
	{
		return "($s1 || $s2)";
	}

	/**
	 * Returns SQL which extracts a substring.
	 *
	 * @param      string String to extract from.
	 * @param      int Offset to start from.
	 * @param      int Number of characters to extract.
	 * @return     string
	 */
	public function subString($s, $pos, $len)
	{
		return "substring($s from $pos" . ($len > -1 ? "for $len" : "") . ")";
	}

	/**
	 * Returns SQL which calculates the length (in chars) of a string.
	 *
	 * @param      string String to calculate length of.
	 * @return     string
	 */
	public function strLength($s)
	{
		return "char_length($s)";
	}

	/**
	 * @see        DBAdapter::getIdMethod()
	 */
	protected function getIdMethod()
	{
		return DBAdapter::ID_METHOD_SEQUENCE;
	}

	/**
	 * Gets ID for specified sequence name.
	 */
	public function getId(PDO $con, $name = null)
	{
		if ($name === null) {
			throw new PropelException("Unable to fetch next sequence ID without sequence name.");
		}
		$stmt = $con->query("SELECT nextval(".$con->quote($name).")");
		$row = $stmt->fetch(PDO::FETCH_NUM);
		return $row[0];
	}

	/**
	 * Returns timestamp formatter string for use in date() function.
	 * @return     string
	 */
	public function getTimestampFormatter()
	{
		return "Y-m-d H:i:s O";
	}

	/**
	 * Returns timestamp formatter string for use in date() function.
	 * @return     string
	 */
	public function getTimeFormatter()
	{
		return "H:i:s O";
	}

	/**
	 * @see        DBAdapter::applyLimit()
	 */
	public function applyLimit(&$sql, $offset, $limit)
	{
		if ( $limit > 0 ) {
			$sql .= " LIMIT ".$limit;
		}
		if ( $offset > 0 ) {
			$sql .= " OFFSET ".$offset;
		}
	}
	
	/**
	 * @see        DBAdapter::random()
	 */
	public function random($seed=NULL)
	{
		return 'random()';
	}
}
