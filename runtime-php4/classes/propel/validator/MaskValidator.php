<?php
/*
 *  $Id: MaskValidator.php,v 1.2 2004/12/04 14:00:42 micha Exp $
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

require_once 'propel/validator/BasicValidator.php';

/**
 * A validator for regular expressions.
 *
 * Below is an example usage for your Propel xml schema file.
 *
 * <code>
 *   <column name="username" type="VARCHAR" size="25" required="true" />
 *
 *   <validator column="username">
 *     <!-- allow only letters and underscore -->
 *     <rule name="mask" value="/^[\w_]+/" message="Username contains invalid characters !" />
 *   </validator>
 * </code>
 *
 * @author Michael Aichler <aichler@mediacluster.de>
 * @author Hans Lellelid <hans@xmpl.org>
 * @version $Revision: 1.2 $
 * @package propel.validator
 */
class MaskValidator extends BasicValidator
{

  /**
  * Prepares the regular expression entered in the XML
  * for use with preg_match().
  *
  * @param string $exp
  * @return string Prepared regular expession.
  */
  function prepareRegexp($exp)
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
  function isValid (&$map, $str)
  {
    return (preg_match($this->prepareRegexp($map->getValue()), $str) != 0);
  }

}