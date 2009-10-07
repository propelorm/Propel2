<?php

/*
 *  $Id: AlternativeCodingStandardsBehavior.php $
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
 * Changes the coding standard of Propel generated Model classes
 *  - Opening brackets always use newline, e.g.
 *     if ($foo) {
 *       ...
 *     } else {
 *       ...
 *     }
 *    Becomes:
 *     if ($foo)
 *     {
 *       ...
 *     }
 *     else
 *     {
 *       ...
 *     }
 *  - closing comments are removed, e.g.
 *     } // save()
 *    Becomes:
 *     }
 *   - tabs are replaced by 2 whitespaces
 *   - comments are stripped (optional)
 *
 * @author     François Zaninotto
 * @version    $Revision: 1066 $
 * @package    propel.engine.behavior
 */
class AlternativeCodingStandardsBehavior extends Behavior
{
	// default parameters value
  protected $parameters = array(
  	'brackets_newline' 				=> 'true',
  	'remove_closing_comments' => 'true',
  	'use_whitespace' 					=> 'true',
  	'tab_size' 								=> 2,
  	'strip_comments'          => 'false'
  );
  
	public function objectFilter(&$script)
	{
		return $this->filter($script);
	}
	
	public function extensionObjectFilter(&$script)
	{
		return $this->filter($script);
	}

	public function peerFilter(&$script)
	{
		return $this->filter($script);
	}

	public function extensionPeerFilter(&$script)
	{
		return $this->filter($script);
	}
	
	public function tableMapFilter(&$script)
	{
		return $this->filter($script);
	}

	/**
	 * Transform the coding standards of a PHP sourcecode string
	 * 
	 * @param string $script A script string to be filtered, passed as reference
	 */
	protected function filter(&$script)
	{
		$filter = array();
		if($this->getParameter('brackets_newline') == 'true') {
			$filter['#^(\t*)\}\h(else|elseif|catch)(.*)\h\{$#m'] = "$1}\n$1$2$3\n$1{";
			$filter['#^(\t*)(\w.*)\h\{$#m'] = "$1$2\n$1{";
		}
		if ($this->getParameter('remove_closing_comments') == 'true') {
			$filter['#^(\t*)} //.*$#m'] = "$1}";
		}
		if ($this->getParameter('use_whitespace') == 'true') {
			$filter['#\t#'] = str_repeat(' ', $this->getParameter('tab_size'));
		}
		
		$script = preg_replace(array_keys($filter), array_values($filter), $script);
		
		if ($this->getParameter('strip_comments') == 'true') {
			$script = self::stripComments($script);
		}
	}
	
	/**
	 * Remove inline and codeblock comments from a PHP code string
	 * @param  string $code The input code
	 * @return string       The input code, without comments
	 */
	public static function stripComments($code)
	{
		$output  = '';
		$commentTokens = array(T_COMMENT, T_DOC_COMMENT);
		foreach (token_get_all($code) as $token) {
			if (is_array($token)) {
		    if (in_array($token[0], $commentTokens)) continue;
				$token = $token[1];
		  }
		  $output .= $token;
		}
		
		return $output;
	}
}