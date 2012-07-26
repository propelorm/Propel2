<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Query;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Query\CriterionCustom;

/**
 * Test class for CriterionCustom.
 *
 * @author François Zaninotto
 */
class CriterionCustomTest extends BaseTestCase
{
    public function testAppendPsToConcatenatesTheValue()
    {
        $cton = new CriterionCustom(new Criteria(), 'A.COL', 'date_part(\'YYYY\', A.COL) = \'2007\'');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $expected = "date_part('YYYY', A.COL) = '2007'";
        $this->assertEquals($expected, $ps);
        $this->assertEquals(array(), $params);
    }
}
