<?php

/*
 *  $Id: OMBuilder.php 186 2005-09-08 13:33:09Z hans $
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

require_once 'propel/engine/builder/DataModelBuilder.php';

/**
 * Baseclass for SQL data dump SQL building classes.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.sql
 */
abstract class DataSQLBuilder extends DataModelBuilder {
	
	/**
	 * The main method in this class, returns the SQL for INSERTing data into a row.
	 * @param DataRow $row The row to process.
	 * @return string
	 */
	public function buildRowSql(DataRow $row)
	{
		$sql = "";
		$platform = $this->getPlatform();
		$table = $this->getTable();
		
		$sql .= "INSERT INTO ".$this->getPlatform()->quoteIdentifier($this->getTable()->getName())." (";		
		
		// add column names to SQL
		$colNames = array();
		foreach ($row->getColumnValues() as $colValue) {
			$colNames[] = $platform->quoteIdentifier($colValue->getColumn()->getName());
		}
		
		$sql .= implode(',', $colNames);
		
		$sql .= ") VALUES (";
		
		$colVals = array();
		foreach ($row->getColumnValues() as $colValue) {
			$colVals[] = $this->getColumnValueSql($colValue);
		}
		
		$sql .= implode(',', $colVals);		
		$sql .= ");
";
		
		return $sql;
	}
	
	/**
	 * Gets the propertly escaped (and quoted) value for a column.
	 * @param ColumnValue $colValue
	 * @return mixed The proper value to be added to the string.
	 */
	protected function getColumnValueSql(ColumnValue $colValue)
	{
		$column = $colValue->getColumn();
		$creoleTypeString = PropelTypes::getCreoleType($column->getPropelType());
		$creoleTypeCode = CreoleTypes::getCreoleCode($creoleTypeString);		
		$method = 'get' . CreoleTypes::getAffix($creoleTypeCode) . 'Sql';
		return $this->$method($colValue->getValue());
	}
	
	
	
	/**
     * Gets a boolean value.
     * Default behavior is true = 1, false = 0.
     * @param boolean $value
     * @return string SQL to insert
     */
    protected function getBooleanSql($value) 
    {      
		return (int) $value;
    }
    

    /**
     * 
     */
    protected function getBlobSql($blob) 
    {        
        // they took magic __toString() out of PHP5.0.0; this sucks
		if (is_object($blob)) {
			return "'" . $this->escape($blob->__toString()) . "'";
		} else {
			return "'" . $this->escape($blob) . "'";
		}
    } 

    /**
     * 
     */
    protected function getClobSql($clob) 
    {
		// they took magic __toString() out of PHP5.0.0; this sucks
		if (is_object($clob)) {
			return "'" . $this->escape($clob->__toString()) . "'";
		} else {
			return "'" . $this->escape($clob) . "'";
		}
    }     

    /**
     * 
     * @param string $value
     * @return void
     */
    protected function getDateSql($value) 
    {
        return $this->getStringSql($value);
    } 
    
    /**
     * 
     * @param double $value
     * @return void
     */
    protected function getDecimalSql($value) 
    {
        return (float) $value;
    }             

    /**
     * 
     * @param double $value
     * @return void
     */
    protected function getDoubleSql($value) 
    {
        return (double) $value;
    } 
        
    /**
     * 
     * @param float $value
     * @return void
     */
    protected function getFloatSql($value) 
    {
        return (float) $value;
    } 

    /**
     * 
     * @param int $value
     * @return void
     */
    protected function getIntSql($value) 
    {
		return (int) $value;
    }

    /**
     * 
     * @return void
     */
    protected function getNullSql() 
    {
        return 'NULL';
    }

    /**
     * 
	 * @param string $value
     * @return void
     */
    protected function getStringSql($value) 
    {
		return "'" . $this->getPlatform()->escapeText($value) . "'";
    }
    
    /**
     * 
     * @param string $value
     * @return void
     */
    protected function getTimeSql($paramIndex, $value) 
    {
		return $this->getStringSql($value);
    }
    
    /**
     * 
     * @param string $value
     * @return void
     */
    protected function getTimestampSql($value) 
    {
		return $this->getStringSql($value);
    }
	
}