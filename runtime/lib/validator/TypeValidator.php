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
 * A validator for validating the (PHP) type of the value submitted.
 *
 * <code>
 *   <column name="some_int" type="INTEGER" required="true"/>
 *
 *   <validator column="some_int">
 *     <rule name="type" value="integer" message="Please specify an integer value for some_int column." />
 *   </validator>
 * </code>
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.runtime.validator
 */
class TypeValidator implements BasicValidator
{
	public function isValid(ValidatorMap $map, $value)
	{
		switch ($map->getValue()) {
			case 'array':
				return is_array($value);
				break;
			case 'bool':
			case 'boolean':
				return is_bool($value);
				break;
			case 'float':
				return is_float($value);
				break;
			case 'int':
			case 'integer':
				return is_int($value);
				break;
			case 'numeric':
				return is_numeric($value);
				break;
			case 'object':
				return is_object($value);
				break;
			case 'resource':
				return is_resource($value);
				break;
			case 'scalar':
				return is_scalar($value);
				break;
			case 'string':
				return is_string($value);
				break;
			case 'function':
				return function_exists($value);
				break;
			default:
				throw new PropelException('Unkonwn type ' . $map->getValue());
				break;
		}
	}
}
