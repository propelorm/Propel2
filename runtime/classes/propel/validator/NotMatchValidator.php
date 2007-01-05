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
 * A validator for regular expressions.
 *
 * This validator will return true, when the passed value does *not* match
 * the regular expression.
 *
 * If you do want to test if the value *matches* an expression, you can use
 * the MatchValidator class instead.
 *
 * Below is an example usage for your Propel xml schema file.
 *
 * <code>
 *   <column name="ISBN" type="VARCHAR" size="20" required="true" />
 *   <validator column="username">
 *     <!-- disallow everything that's not a digit or minus -->
 *     <rule
 *       name="notMatch"
 *       value="/[^\d-]+/"
 *       message="Please enter a valid email adress." />
 *   </validator>
 * </code>
 *
 * @author     Michael Aichler <aichler@mediacluster.de>
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.validator
 */
class NotMatchValidator implements BasicValidator
{
	/**
	 * Prepares the regular expression entered in the XML
	 * for use with preg_match().
	 * @param      string $exp
	 * @return     string Prepared regular expession.
	 */
	private function prepareRegexp($exp)
	{
		// remove surrounding '/' marks so that they don't get escaped in next step
		if ($exp{0} !== '/' || $exp{strlen($exp)-1} !== '/' ) {
			$exp = '/' . $exp . '/';
		}

		// if they did not escape / chars; we do that for them
		$exp = preg_replace('/([^\\\])\/([^$])/', '$1\/$2', $exp);

		return $exp;
	}

	/**
	 * Whether the passed string matches regular expression.
	 */
	public function isValid (ValidatorMap $map, $str)
	{
		return (preg_match($this->prepareRegexp($map->getValue()), $str) == 0);
	}
}
