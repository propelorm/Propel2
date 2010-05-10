<?php

include_once 'phing/system/io/StringReader.php';

class PropelStringReader extends StringReader
{
	public function eof()
	{
		return $this->currPos == strlen($this->_string);
	}
}