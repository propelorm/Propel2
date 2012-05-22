<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Query;

/**
 * Class that implements SPL Iterator interface.  This allows foreach () to
 * be used w/ Criteria objects.  Probably there is no performance advantage
 * to doing it this way, but it makes sense -- and simpler code.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class CriterionIterator implements \Iterator
{
    private $idx = 0;
    private $criteria;
    private $criteriaKeys;
    private $criteriaSize;

    public function __construct(Criteria $criteria)
    {
        $this->criteria = $criteria;
        $this->criteriaKeys = $criteria->keys();
        $this->criteriaSize = count($this->criteriaKeys);
    }

    public function rewind()
    {
        $this->idx = 0;
    }

    public function valid()
    {
        return $this->idx < $this->criteriaSize;
    }

    public function key()
    {
        return $this->criteriaKeys[$this->idx];
    }

    public function current()
    {
        return $this->criteria->getCriterion($this->criteriaKeys[$this->idx]);
    }

    public function next()
    {
        $this->idx++;
    }
}
