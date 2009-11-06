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

require_once dirname(__FILE__) . '/NestedSetBehaviorObjectBuilderModifier.php';
require_once dirname(__FILE__) . '/NestedSetBehaviorPeerBuilderModifier.php';
 
/**
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author     François Zaninotto
 * @package    propel.engine.behavior.nestedset
 */
class NestedSetBehavior extends Behavior
{
  // default parameters value
  protected $parameters = array(
    'add_columns'  => 'true',
    'left_column'  => 'tree_left',
    'right_column' => 'tree_right',
    'level_column' => 'tree_level',
    'use_scope'    => 'false',
    'scope_column' => 'tree_scope'
  );

  protected $objectBuilderModifier, $peerBuilderModifier;
      
  /**
   * Add the left, right and scope to the current table
   */
  public function modifyTable()
  {
    if ($this->getParameter('add_columns') == 'true')
    {
      $this->getTable()->addColumn(array(
        'name' => $this->getParameter('left_column'),
        'type' => 'INTEGER'
      ));
      $this->getTable()->addColumn(array(
        'name' => $this->getParameter('right_column'),
        'type' => 'INTEGER'
      ));
      $this->getTable()->addColumn(array(
        'name' => $this->getParameter('level_column'),
        'type' => 'INTEGER'
      ));
      if ($this->getParameter('use_scope') == 'true') {
        $this->getTable()->addColumn(array(
          'name' => $this->getParameter('scope_column'),
          'type' => 'INTEGER'
        ));
      }
    }
  }
  
  public function getObjectBuilderModifier()
  {
    if (is_null($this->objectBuilderModifier))
    {
      $this->objectBuilderModifier = new NestedSetBehaviorObjectBuilderModifier($this);
    }
    return $this->objectBuilderModifier;
  }
  
  public function getPeerBuilderModifier()
  {
    if (is_null($this->peerBuilderModifier))
    {
      $this->peerBuilderModifier = new NestedSetBehaviorPeerBuilderModifier($this);
    }
    return $this->peerBuilderModifier;
  }
  
  public function useScope()
	{
		return $this->getParameter('use_scope') == 'true';
	}

}