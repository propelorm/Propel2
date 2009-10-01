<?php

class TimestampableBehavior extends Behavior
{
  // default parameters value
  protected $parameters = array(
    'add_columns'    => 'true',
    'create_column' => 'created_at',
    'update_column' => 'updated_at'
  );
  
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

}