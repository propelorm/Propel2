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

require_once 'propel/engine/platform/DefaultPlatform.php';

/**
 * Postgresql Platform implementation.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.engine.platform
 */
class PgsqlPlatform extends DefaultPlatform {

	/**
	 * Initializes db specific domain mapping.
	 */
	protected function initialize()
	{
		parent::initialize();
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, "BOOLEAN"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TINYINT, "INT2"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::SMALLINT, "INT2"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BIGINT, "INT8"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, "FLOAT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, "DOUBLE PRECISION"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "TEXT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "BYTEA"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "BYTEA"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "BYTEA"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, "BYTEA"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, "TEXT"));
	}

	/**
	 * @see        Platform#getNativeIdMethod()
	 */
	public function getNativeIdMethod()
	{
		return Platform::SERIAL;
	}

	/**
	 * @see        Platform#getAutoIncrement()
	 */
	public function getAutoIncrement()
	{
		return "";
	}

	/**
	 * @see        Platform#getMaxColumnNameLength()
	 */
	public function getMaxColumnNameLength()
	{
		return 32;
	}

	/**
	 * Escape the string for RDBMS.
	 * @param      string $text
	 * @return     string
	 */
	public function disconnectedEscapeText($text)
	{
		if (function_exists('pg_escape_string')) {
			return pg_escape_string($text);
		} else {
			return parent::disconnectedEscapeText($text);
		}
	}

	/**
	 * @see        Platform::getBooleanString()
	 */
	public function getBooleanString($b)
	{
		// parent method does the checking for allowes tring
		// representations & returns integer
		$b = parent::getBooleanString($b);
		return ($b ? "'t'" : "'f'");
	}

	/**
	 * @see        Platform::supportsNativeDeleteTrigger()
	 */
	public function supportsNativeDeleteTrigger()
	{
		return true;
	}

	/**
	 * @see        Platform::hasSize(String)
	 * TODO collect info for all platforms
	 */
	public function hasSize($sqlType)
	{
		return !("BYTEA" == $sqlType || "TEXT" == $sqlType);
	}

	/**
	 * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
	 * @return     boolean
	 */
	public function hasStreamBlobImpl()
	{
		return true;
	}
}
