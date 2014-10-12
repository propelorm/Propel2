<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.10.14
 * Time: 23:32
 */

namespace Propel\Tests\Generator\Builder;


use Propel\Generator\Builder\PhpdocFixer;
use Propel\Tests\TestCase;

class PhpdocFixerTest extends TestCase
{

    public function testFunction()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<'EOF'
    public function save()
    {
        $bla '\Car ab');
    }

            /**
             * Get the associated Brand object
             *
             * @param  ConnectionInterface $con Optional Connection object.
             * @return Brand The associated Brand object.
             * @throws PropelException
             */
    public function getBrand()
    {
EOF;

        $expected = <<<'EOF'
    public function save()
    {
        $bla '\Car ab');
    }

    /**
     * Get the associated Brand object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return Brand The associated Brand object.
     * @throws PropelException
     */
    public function getBrand()
    {
EOF;

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testEmptyLines2()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<'EOF'

    /**
     * The value for the name field.

     * @var string
     */
    protected $bla;
EOF;

        $expected = <<<'EOF'

    /**
     * The value for the name field.
     * @var string
     */
    protected $bla;
EOF;

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testEmptyLines()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<'EOF'

    /**
    *





         dddd
    */
    protected $bla;
EOF;

        $expected = '
    /**
     *
     * ddd
     */
    protected $bla;';

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testForbiddenAreas3()
    {
        $fixer = new PhpdocFixer();

        $docBlock = "
\$var = <<<EOF
/**
    * My Class
*/
class TestClass {
}
EOF;

/**
    * phdoc
*/
\$var = <<<EOF
/**
* My Class
*/
class TestClass {
   \$var = <<<EOF
   /**
   *
      */
   bla
   EOF;
}
EOF;
";

        $expected = '
$var = <<<EOF
/**
    * My Class
*/
class TestClass {
}
EOF;

/**
    * phdoc
*/
$var = <<<EOF
/**
* My Class
*/
class TestClass {
   $var = <<<EOF
   /**
   *
      */
   bla
   EOF;
}
EOF;
';

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testForbiddenAreas2()
    {
        $fixer = new PhpdocFixer();

        $docBlock = "
\$var = \"
/**
    * My Class
*/
class TestClass {
}
\\\\\\\\\";

/**
    * phdoc
*/
\$var = \"
/**
* My Class
*/
class TestClass {
   \$var = \\\"
   /**
   *
      */
   bla
   \\\"
}
\";
";

        $expected = <<<'EOF'

$var = "
/**
    * My Class
*/
class TestClass {
}
\\\\";

/**
 * phdoc
 */
$var = "
/**
* My Class
*/
class TestClass {
   $var = \"
   /**
   *
      */
   bla
   \"
}
";

EOF;

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testForbiddenAreas()
    {
        $fixer = new PhpdocFixer();

        $docBlock = "
\$var = <<<EOF
/**
* My Class
*/
class TestClass {
}
EOF;
";

        $expected = "
\$var = <<<EOF
/**
* My Class
*/
class TestClass {
}
EOF;
";

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }


    public function testClass()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<EOF
/**
* My Class
*/
class TestClass {
}
EOF;

        $expected = <<<EOF
/**
 * My Class
 */
class TestClass {
}
EOF;

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testIndentation4()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<EOF
/**
* My Class
*/
class TestClass {
    public function test() {

       /**
            *
         */
        if (this_happens) {
            then;

            /**
            ***
          */
            Woot;

            echo <<<EFFF
     My string - don't touch it
     /**
    **
        */
       \$anotherVar = '';
EFFF;
        }

    }
}

EOF;

        $expected = <<<'EOF'
/**
 * My Class
 */
class TestClass {
    public function test() {

        /**
         *
         */
        if (this_happens) {
            then;

            /**
             ***
             */
            Woot;

            echo <<<EFFF
     My string - don't touch it
     /**
    **
        */
       $anotherVar = '';
EFFF;
        }

    }
}

EOF;

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testIndentation3()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<EOF
    /**
 * Simple docBlock
                *
 * @var int
 */
     protected \$var;
EOF;

        $expected = <<<EOF
     /**
      * Simple docBlock
      *
      * @var int
      */
     protected \$var;
EOF;

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testIndentation2()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<EOF
/**
 * Simple docBlock
                *
 * @var int
 */
     protected \$var;

    /**
 * Simple docBlock
                *
 * @var int
 */
         protected \$var;

    /**
 * Simple docBlock
                *
 * @var int
 */
     protected \$var;

EOF;

        $expected = <<<eOF
     /**
      * Simple docBlock
      *
      * @var int
      */
     protected \$var;

         /**
          * Simple docBlock
          *
          * @var int
          */
         protected \$var;

     /**
      * Simple docBlock
      *
      * @var int
      */
     protected \$var;

eOF;


        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);
    }

    public function testIndentation()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<EOF

    /**
 * Simple docBlock
                *
 * @var int
 */
     protected \$var;

EOF;

        $expected = <<<EOF

     /**
      * Simple docBlock
      *
      * @var int
      */
     protected \$var;

EOF;

        $result = $fixer->fix($docBlock);
        $this->assertEquals($expected, $result);

    }

    public function testSimple()
    {
        $fixer = new PhpdocFixer();

        $docBlock = <<<EOF
/**
 * Simple docBlock
 *
 * @var int
 */
EOF;

        $this->assertEquals($docBlock, $fixer->fix($docBlock));

    }

} 