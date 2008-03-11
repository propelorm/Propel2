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

require_once 'propel/engine/builder/sql/DataSQLBuilder.php';

/**
 * PostgreSQL class for building data dump SQL.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder.sql.pgsql
 */
class PgsqlDataSQLBuilder extends DataSQLBuilder {

	/**
	 * The largets serial value encountered this far.
	 *
	 * @var        int
	 */
	private $maxSeqVal;

	/**
	 * Construct a new PgsqlDataSQLBuilder object.
	 *
	 * @param      Table $table
	 */
	public function __construct(Table $table)
	{
		parent::__construct($table);
	}

	/**
	 * The main method in this class, returns the SQL for INSERTing data into a row.
	 * @param      DataRow $row The row to process.
	 * @return     string
	 */
	public function buildRowSql(DataRow $row)
	{
		$sql = parent::buildRowSql($row);

		$table = $this->getTable();

		if ($table->hasAutoIncrementPrimaryKey() && $table->getIdMethod() == IDMethod::NATIVE) {
			foreach ($row->getColumnValues() as $colValue) {
				if ($colValue->getColumn()->isAutoIncrement()) {
					if ($colValue->getValue() > $this->maxSeqVal) {
						$this->maxSeqVal = $colValue->getValue();
					}
				}
			}
		}

		return $sql;
	}

	public function getTableEndSql()
	{
		$table = $this->getTable();
		$sql = "";
		if ($table->hasAutoIncrementPrimaryKey() && $table->getIdMethod() == IDMethod::NATIVE) {
			$seqname = $this->prefixTablename($this->getDDLBuilder()->getSequenceName());
			$sql .= "SELECT pg_catalog.setval('$seqname', ".((int)$this->maxSeqVal).");
";
		}
		return $sql;
	}

	/**
	 * Get SQL value to insert for Postgres BOOLEAN column.
	 * @param      boolean $value
	 * @return     string The representation of boolean for Postgres ('t' or 'f').
	 */
	protected function getBooleanSql($value)
	{
		if ($value === 'f' || $value === 'false' || $value === "0") {
			$value = false;
		}
		return ($value ? "'t'" : "'f'");
	}

	/**
	 *
	 * @param      mixed $blob Blob object or string containing data.
	 * @return     string
	 */
	protected function getBlobSql($blob)
	{
		// they took magic __toString() out of PHP5.0.0; this sucks
		if (is_object($blob)) {
			$blob = $blob->__toString();
		}
		return "'" . pg_escape_bytea($blob) . "'";
	}

}
