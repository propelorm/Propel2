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
 * A class for holding a column default value.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class ColumnDefaultValue {

	const TYPE_VALUE = "value";
	const TYPE_EXPR = "expr";

	/**
	 * @var        string The default value, as specified in the schema.
	 */
	private $value;

	/**
	 * @var        string The type of value represented by this object (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR).
	 */
	private $type = ColumnDefaultValue::TYPE_VALUE;

	/**
	 * Creates a new DefaultValue object.
	 *
	 * @param      string $value The default value, as specified in the schema.
	 * @param      string $type The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
	 */
	public function __construct($value, $type = null)
	{
		$this->setValue($value);
		if ($type !== null) {
			$this->setType($type);
		}
	}

	/**
	 * @return     string The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param      string $type The type of default value (DefaultValue::TYPE_VALUE or DefaultValue::TYPE_EXPR)
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * Convenience method to indicate whether the value in this object is an expression (as opposed to simple value).
	 *
	 * @return     boolean Whether value this object holds is an expression.
	 */
	public function isExpression()
	{
		return ($this->type == self::TYPE_EXPR);
	}

	/**
	 * @return     string The value, as specified in the schema.
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param      string $value The value, as specified in the schema.
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}


}
