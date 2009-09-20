<?php

/*
 *  $Id: RelationMap.php 1153 2009-09-20 18:08:53Z francois $
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
 * RelationMap is used to model a database relationship.
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
 * classes.
 *
 * @author     Francois Zaninotto
 * @version    $Revision: 1153 $
 * @package    propel.map
 */
class RelationMap {

  const
    HAS_ONE = 1,
    HAS_MANY = 2;
    
  protected 
    $name,
    $type,
    $localTable,
    $foreignTable,
    $columnMapping,
    $onUpdate, $onDelete;

  /**
   * Constructor.
   *
   * @param      string $name Name of the database.
   */
  public function __construct($name)
  {
    $this->name = $name;
  }
  
  /**
   * Get the name of this database.
   *
   * @return     string The name of the database.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set the type
   *
   * @param      integer $type The relation type (either self::HAS_ONE, or self::HAS_MANY)
   */
  public function setType($type)
  {
    $this->type = $type;
  }

  /**
   * Get the type
   *
   * @return      integer the relation type
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set the local table
   *
   * @param      TableMap $table The local table for this relationship
   */
  public function setLocalTable($table)
  {
    $this->localTable = $table;
  }

  /**
   * Get the local table
   *
   * @return      TableMap The local table for this relationship
   */
  public function getLocalTable()
  {
    return $this->localTable;
  }

  /**
   * Set the foreign table
   *
   * @param      TableMap $table The foreign table for this relationship
   */
  public function setForeignTable($table)
  {
    $this->foreignTable = $table;
  }

  /**
   * Get the foreign table
   *
   * @return      TableMap The foreign table for this relationship
   */
  public function getForeignTable()
  {
    return $this->foreignTable;
  }
}
