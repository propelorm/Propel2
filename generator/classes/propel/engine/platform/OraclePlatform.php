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
 * Oracle Platform implementation.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.engine.platform
 */
class OraclePlatform extends DefaultPlatform {

	/**
	 * Initializes db specific domain mapping.
	 */
	protected function initialize()
	{
		parent::initialize();
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, "NUMBER", "1", "0"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TINYINT, "NUMBER", "3", "0"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::SMALLINT, "NUMBER", "5", "0"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::INTEGER, "NUMBER"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BIGINT, "NUMBER", "20", "0"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, "NUMBER"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, "FLOAT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DECIMAL, "NUMBER"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, "NUMBER"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::VARCHAR, "NVARCHAR2"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "NVARCHAR2", "2000")); 
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TIME, "TIME")); 
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, "DATE")); 
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, "TIMESTAMP")); 
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "LONG RAW"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "BLOB"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "LONG RAW"));
	}

	/**
	 * @see        Platform#getMaxColumnNameLength()
	 */
	public function getMaxColumnNameLength()
	{
		return 30;
	}

	/**
	 * @see        Platform#getNativeIdMethod()
	 */
	public function getNativeIdMethod()
	{
		return Platform::SEQUENCE;
	}

	/**
	 * @see        Platform#getAutoIncrement()
	 */
	public function getAutoIncrement()
	{
		return "";
	}

	/**
	 * @see        Platform::supportsNativeDeleteTrigger()
	 */
	public function supportsNativeDeleteTrigger()
	{
		return true;
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
