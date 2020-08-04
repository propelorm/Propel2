<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\CustomCriterion;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for CustomCriterion.
 *
 * @author François Zaninotto
 */
class CustomCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToConcatenatesTheValue()
    {
        $cton = new CustomCriterion(new Criteria(), 'date_part(\'YYYY\', A.COL) = \'2007\'');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $expected = "date_part('YYYY', A.COL) = '2007'";
        $this->assertEquals($expected, $ps);
        $this->assertEquals([], $params);
    }
}
