<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Util;

use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Tests\TestCase;

/**
 * Tests for PropelTemplate class
 *
 */
class PropelTemplateTest extends TestCase
{
    public function testRenderStringNoParam()
    {
        $t = new PropelTemplate();
        $t->setTemplate('Hello, <?php echo 1 + 2 ?>');
        $res = $t->render();
        $this->assertEquals('Hello, 3', $res);
    }

    public function testRenderStringOneParam()
    {
        $t = new PropelTemplate();
        $t->setTemplate('Hello, <?php echo $name ?>');
        $res = $t->render(array('name' => 'John'));
        $this->assertEquals('Hello, John', $res);
    }

    public function testRenderStringParams()
    {
        $time = time();
        $t = new PropelTemplate();
        $t->setTemplate('Hello, <?php echo $name ?>, it is <?php echo $time ?> to go!');
        $res = $t->render(array('name' => 'John', 'time' => $time));
        $this->assertEquals('Hello, John, it is ' . $time . ' to go!', $res);
    }

    public function testRenderFile()
    {
        $t = new PropelTemplate();
        $t->setTemplateFile(dirname(__FILE__).'/template.php');
        $res = $t->render(array('name' => 'John'));
        $this->assertEquals('Hello, John', $res);
    }
}
