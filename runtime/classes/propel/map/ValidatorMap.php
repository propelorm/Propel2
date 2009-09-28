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
 * ValidatorMap is used to model a column validator.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author     Michael Aichler <aichler@mediacluster.de>
 * @version    $Revision$
 * @package    propel.map
 */
class ValidatorMap
{
	/** rule name of this validator */
	private $name;
	/** the dot-path to class to use for validator */
	private $classname;
	/** value to check against */
	private $value;
	/** execption message thrown on invalid input */
	private $message;
	/** related column */
	private $column;

	public function __construct($containingColumn)
	{
		$this->column = $containingColumn;
	}

	public function getColumn()
	{
		return $this->column;
	}

	public function getColumnName()
	{
		return $this->column->getColumnName();
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setClass($classname)
	{
		$this->classname = $classname;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function setMessage($message)
	{
		$this->message = $message;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getClass()
	{
		return $this->classname;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function getMessage()
	{
		return $this->message;
	}
}
