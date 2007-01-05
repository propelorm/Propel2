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
 * A <code>NameGenerator</code> implementation for table-specific
 * constraints.  Conforms to the maximum column name length for the
 * type of database in use.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class ConstraintNameGenerator implements NameGenerator {
	/**
	 * Conditional compilation flag.
	 */
	const DEBUG = false;

	/**
	 * First element of <code>inputs</code> should be of type {@link Database}, second
	 * should be a table name, third is the type identifier (spared if
	 * trimming is necessary due to database type length constraints),
	 * and the fourth is a <code>Integer</code> indicating the number
	 * of this contraint.
	 *
	 * @see        NameGenerator
	 * @throws     EngineException
	 */
	public function generateName($inputs)
	{

		$db = $inputs[0];
		$name = $inputs[1];
		$namePostfix = $inputs[2];
		$constraintNbr = (string) $inputs[3];

		// Calculate maximum RDBMS-specific column character limit.
		$maxBodyLength = -1;
		try {
			$maxColumnNameLength = (int) $db->getPlatform()->getMaxColumnNameLength();
			$maxBodyLength = ($maxColumnNameLength - strlen($namePostfix)
					- strlen($constraintNbr) - 2);

			if (self::DEBUG) {
				print("maxColumnNameLength=" . $maxColumnNameLength
						. " maxBodyLength=" . $maxBodyLength . "\n");
			}
		} catch (EngineException $e) {
			echo $e;
			throw $e;
		}

		// Do any necessary trimming.
		if ($maxBodyLength !== -1 && strlen($name) > $maxBodyLength) {
			$name = substr($name, 0, $maxBodyLength);
		}

		$name .= self::STD_SEPARATOR_CHAR . $namePostfix
			. self::STD_SEPARATOR_CHAR . $constraintNbr;

		return $name;
	}
}
