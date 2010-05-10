<?php

include_once 'phing/system/io/StringReader.php';

class PropelStringReader extends StringReader
{
	/**
	 * @var string
	 */
	protected $_string;
    
	/**
	 * @var int
	 */
	protected $currPos = 0;

	public function eof()
	{
		return $this->currPos == strlen($this->_string);
	}
}