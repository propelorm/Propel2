<?php
/*
 *  $Id: PreOrderNodeIterator.php,v 1.1 2004/10/30 17:51:09 micha Exp $
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
 * Pre-order node iterator for Node objects.
 *
 * @author Kaspars Jaudzems <kasparsj@navigators.lv> (Propel)
 * @author Dave Lawson <dlawson@masterytech.com>
 * @version $Revision: 1.1 $
 * @package propel.om
 */
class PreOrderNodeIterator
{
  var $topNode = null;
  
  var $curNode = null;

  var $querydb = false;
  
  function PreOrderNodeIterator(&$node, &$opts) 
  {
    $this->topNode =& $node;
    $this->curNode =& $node;
    
    if (isset($opts['querydb'])) {
      $this->querydb =& $opts['querydb'];
    }
  }
  
  function rewind() 
  {
    $this->curNode =& $this->topNode;
  }
  
  function valid() 
  {
    return ($this->curNode !== null);
  }
  
  function & current() 
  {
    return $this->curNode;
  }
  
  function key() 
  {
    return $this->curNode->getNodePath();
  }

  function & next() 
  {
    if ($this->valid())
    {
      $nextNode =& $this->curNode->getFirstChildNode($this->querydb);

      while ($nextNode === null)
      {
        if ($this->curNode === null || $this->curNode->equals($this->topNode)) {
          break;
        }

        $nextNode =& $this->curNode->getSiblingNode(false, $this->querydb);
        
        if ($nextNode === null) {
          $this->curNode =& $this->curNode->getParentNode($this->querydb);
        }
      }
      
      $this->curNode =& $nextNode;
    }
    
    return $this->curNode;
  }
  
}
