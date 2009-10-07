<?php

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