<?php
/*
 *  $Id: ValidatorMap.php,v 1.3 2004/12/04 13:55:42 micha Exp $
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
 * These classes in themselves do not do any database metadata lookups, but instead 
 * are used by the MapBuilder classes that were generated for your datamodel. The 
 * MapBuilder that was created for your datamodel build a representation of your
 * database by creating instances of the DatabaseMap, TableMap, ColumnMap, etc. 
 * classes. See propel/templates/om/php4/MapBuilder.tpl and the classes generated
 * by that template for your datamodel to further understand how these are put 
 * together.
 * 
 * @author Michael Aichler <aichler@mediacluster.de>
 * @version $Revision: 1.3 $
 * @package propel.map
 */
class ValidatorMap
{
  /** rule name of this validator */
  var $name;
  /** the dot-path to class to use for validator */
  var $classname;
  /** value to check against */
  var $value;
  /** execption message thrown on invalid input */
  var $message;
  /** related column */
  var $column;

  /**
  * @param ColumnMap $containingColumn
  * @return ValidatorMap
  */
  function ValidatorMap(&$containingColumn)
  {
    $this->column =& $containingColumn;
  }

  /**
  * @return ColumnMap
  */
  function & getColumn()
  {
    return $this->column;
  }

  function getColumnName()
  {
    return $this->column->getName();
  }

  function setName($name)
  {
    $this->name = $name;
  }

  function setClass($classname)
  {
    $this->classname = $classname;
  }

  function setValue($value)
  {
    $this->value = $value;
  }

  function setMessage($message)
  {
    $this->message = $message;
  }

  function getName()
  {
    return $this->name;
  }

  function getClass()
  {
    return $this->classname;
  }

  function getValue()
  {
    return $this->value;
  }

  function getMessage()
  {
    return $this->message;
  }
}