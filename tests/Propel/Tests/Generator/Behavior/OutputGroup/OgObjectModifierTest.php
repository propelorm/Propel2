<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\OutputGroup;

use Propel\Generator\Behavior\OutputGroup\ObjectWithOutputGroupInterface;
use Propel\Generator\Behavior\OutputGroup\OgObjectModifier;
use Propel\Generator\Behavior\OutputGroup\OutputGroupBehavior;
use Propel\Tests\TestCase;


/**
 */
class OgObjectModifierTest extends TestCase
{


    public function classDeclarationDataProvider()
    {
        return [
            ['abstract class FooQuery implements BarInterface'],
            ['class FooQuery extends BazClass implements BarInterface'],
            ["class FooQuery\nimplements BarInterface,\nBazInterface"],
        ];
    }

    /**
     * @dataProvider classDeclarationDataProvider
     * @return void
     */
    public function testAddInterfaceDeclaration(string $classDeclaration)
    {
        $interface = ObjectWithOutputGroupInterface::class;
        $pattern = <<<EOT
/**
 * asdf
 */
%s
{     
EOT;
        $script = sprintf($pattern, $classDeclaration);
        $expected = sprintf($pattern, "$classDeclaration, \\$interface");
        $modifier = new OgObjectModifier(new OutputGroupBehavior());
        $actual = $this->callMethod($modifier, 'addInterfaceDeclaration', [$script]);

        $this->assertEquals($expected, $actual);
    }
}
