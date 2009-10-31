<?php

/*
 *  $Id: TimestampableBehavior.php 1262 2009-10-26 20:54:39Z francois $
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
 * Gives a model class the ability to track creation and last modification dates
 * Uses two additional columns storing the creation and update date
 *
 * @author     François Zaninotto
 * @version    $Revision: 1066 $
 * @package    propel.engine.behavior
 */
class TimestampableBehavior extends Behavior
{
  // default parameters value
  protected $parameters = array(
    'add_columns'   => 'true',
    'create_column' => 'created_at',
    'update_column' => 'updated_at'
  );
  
  /**
   * Add the create_column and update_columns to the current table
   */
  public function modifyTable()
  {
    if ($this->getParameter('add_columns') == 'true')
    {
      $this->getTable()->addColumn(array(
        'name' => $this->getParameter('create_column'),
        'type' => 'TIMESTAMP'
      ));
      $this->getTable()->addColumn(array(
        'name' => $this->getParameter('update_column'),
        'type' => 'TIMESTAMP'
      ));
    }
  }
  
  /**
   * Get the setter of one of the columns of the behavior
   * 
   * @param  string $column One of the behavior colums, 'create_column' or 'update_column'
   * @return string The related setter, 'setCreatedOn' or 'setUpdatedOn'
   */
  protected function getColumnSetter($column)
  {
    return 'set' . $this->getColumnForParameter($column)->getPhpName();
  }
  
  /**
   * Add code in ObjectBuilder::preSave
   *
   * @return string The code to put at the hook
   */
  public function preSave()
  {
    return "\$this->" . $this->getColumnSetter('update_column') . "(time());";
  }
  
  /**
   * Add code in ObjectBuilder::preInsert
   *
   * @return string The code to put at the hook
   */
  public function preInsert()
  {
    return "\$this->" . $this->getColumnSetter('create_column') . "(time());";    
  }
}