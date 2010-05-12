<?php

include_once 'phing/system/io/Reader.php';

class PropelStringReader extends Reader
{
	/**
	 * @var string
	 */
	protected $_string;
	
	/**
	* @var int
	*/
	protected $mark = 0;
    
	/**
	 * @var int
	 */
	protected $currPos = 0;

	public function __construct($string)
	{
		$this->_string = $string;
	}

	public function skip($n)
	{
		$this->currPos = $this->currPos + $n;
	}
	 
	public function eof()
	{
		return $this->currPos == strlen($this->_string);
	}

	public function read($len = null)
	{
		if ($len === null) {
			return $this->_string;
		} else {            
			if ($this->currPos >= strlen($this->_string)) {
				return -1;
			}            
			$out = substr($this->_string, $this->currPos, $len);
			$this->currPos += $len;
			return $out;
		}
	}

	public function mark()
	{
		$this->mark = $this->currPos;
	}

	public function reset()
	{
		$this->currPos = $this->mark;
	}

	public function close() {}

	public function open() {}

	public function ready() {}

	public function markSupported() 
	{
		return true;
	}
	
	public function getResource() 
	{
		return '(string) "'.$this->_string . '"';
	}
}