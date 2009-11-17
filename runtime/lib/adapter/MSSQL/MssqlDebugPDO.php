<?php

/**
 * dblib doesn't support transactions so we need to add a workaround for transactions, last insert ID, and quoting
 *
 * @package    propel.runtime.adapter.MSSQL
 */
class MssqlDebugPDO extends MssqlPropelPDO
{
	public $useDebug = true;
}
