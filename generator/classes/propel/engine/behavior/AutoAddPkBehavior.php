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
 * Adds a primary key to models defined without one
 *
 * @author     François Zaninotto
 * @version    $Revision: 1066 $
 * @package    propel.engine.behavior
 */
class AutoAddPkBehavior extends Behavior
{
  // default parameters value
  protected $parameters = array(
    'name'          => 'id',
    'autoIncrement' => 'true',
    'type'          => 'INTEGER'
  );

  /**
   * Copy the behavior to the database tables
   * Only for tables that have no Pk
   */
  public function modifyDatabase()
  {
    foreach ($this->getDatabase()->getTables() as $table)
    {
      if(!$table->hasPrimaryKey())
      {
        $b = clone $this;
        $table->addBehavior($b);
      }
    }
  }
  
  /**
   * Add the primary key to the current table
   */
  public function modifyTable()
  {
    if (!$this->getTable()->hasPrimaryKey())
    {
      $columnAttributes = array_merge(array('primaryKey' => 'true'), $this->getParameters());
      $this->getTable()->addColumn($columnAttributes);
    }
  }
}