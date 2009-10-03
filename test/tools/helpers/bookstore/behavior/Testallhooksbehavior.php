<?php

class TestAllHooksBehavior extends Behavior
{
  protected $tableModifier, $objectBuilderModifier, $peerBuilderModifier;
  
  public function getTableModifier()
  {
    if (is_null($this->tableModifier))
    {
      $this->tableModifier = new TestAllHooksTableModifier($this);
    }
    return $this->tableModifier;
  }
  
  public function getObjectBuilderModifier()
  {
    if (is_null($this->objectBuilderModifier))
    {
      $this->objectBuilderModifier = new TestAllHooksObjectBuilderModifier($this);
    }
    return $this->objectBuilderModifier;
  }
  
  public function getPeerBuilderModifier()
  {
    if (is_null($this->peerBuilderModifier))
    {
      $this->peerBuilderModifier = new TestAllHooksPeerBuilderModifier($this);
    }
    return $this->peerBuilderModifier;
  }
}

class TestAllHooksTableModifier
{
  protected $behavior, $table;
  
  public function __construct($behavior)
  {
    $this->behavior = $behavior;
    $this->table = $behavior->getTable();
  }
  
  public function modifyTable()
  {
    $this->table->addColumn(array(
      'name' => 'test',
      'type' => 'TIMESTAMP'
    ));
  }
}

class TestAllHooksObjectBuilderModifier
{ 
  public function objectAttributes()
  {
    return 'public $customAttribute = 1;';
  }
  
  public function preSave()
  {
    return '$this->preSave = 1;$this->preSaveIsAfterSave = isset($affectedRows);';
  }
  
  public function postSave()
  {
    return '$this->postSave = 1;$this->postSaveIsAfterSave = isset($affectedRows);';
  }

  public function preInsert()
  {
    return '$this->preInsert = 1;$this->preInsertIsAfterSave = isset($affectedRows);';
  }
  
  public function postInsert()
  {
    return '$this->postInsert = 1;$this->postInsertIsAfterSave = isset($affectedRows);';
  }
  
  public function preUpdate()
  {
    return '$this->preUpdate = 1;$this->preUpdateIsAfterSave = isset($affectedRows);';
  }
  
  public function postUpdate()
  {
    return '$this->postUpdate = 1;$this->postUpdateIsAfterSave = isset($affectedRows);';
  }
  
  public function preDelete()
  {
    return '$this->preDelete = 1;$this->preDeleteIsBeforeDelete = isset(Table3Peer::$instances[$this->id]);';
  }
  
  public function postDelete()
  {
    return '$this->postDelete = 1;$this->postDeleteIsBeforeDelete = isset(Table3Peer::$instances[$this->id]);';
  }
  
  public function objectMethods()
  {
    return 'public function hello() { return "hello"; }';
  }
  
  public function objectFilter(&$string)
  {
    $string .= 'class testObjectFilter {}';
  }
}

class TestAllHooksPeerBuilderModifier
{ 
  public function staticAttributes()
  {
    return 'public static $customStaticAttribute = 1;';
  }
  
  public function staticMethods()
  {
    return 'public static function hello() { return "hello"; }';
  }
  
  public function preSelect()
  {
    return '$con->preSelect = 1;';
  }

  public function peerFilter(&$string)
  {
    $string .= 'class testPeerFilter {}';
  }

}