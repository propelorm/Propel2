<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Util\PhpParser;
use \Propel\Tests\TestCase;

/**
 *
 */
class IconvTransliterationTest extends TestCase
{

    /**
     * Excerpt from http://php.net/manual/en/function.iconv.php#74101
     * "Please note that iconv('UTF-8', 'ASCII//TRANSLIT', ...) doesn't work properly when locale category LC_CTYPE is set to C or POSIX. You must choose another locale otherwise all non-ASCII characters will be replaced with question marks."
     */
    public function testIconvSupportedLocale()
    {
        if (!function_exists('iconv')) {
            $this->markTestSkipped();
        }
        $LC_CTYPE = setlocale(LC_CTYPE, 0);
        $this->assertNotEquals('C', $LC_CTYPE, 'iconv transliteration does not work properly when locale category LC_CTYPE is set to C or POSIX');
        $this->assertNotEquals('POSIX', $LC_CTYPE, 'iconv transliteration does not work properly when locale category LC_CTYPE is set to C or POSIX');
    }

    public static function iconvTransliterationSlugProvider()
    {
        return [
            ['foo', 'foo'],
            ['fôo', 'foo'],
            ['€', 'EUR'],
            ['CŠŒŽšœžŸµÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝßàáâãäåæçèéêëìíîïñòóôõöùúûüýÿ', 'CSOEZsoezYuAAAAAAAECEEEEIIIINOOOOOUUUUYssaaaaaaaeceeeeiiiinooooouuuuyy'],
            ['ø', '?'],
            ['Ø', '?'],
            ['¥Ðð', '???'],
        ];
    }

    /**
     * @dataProvider iconvTransliterationSlugProvider
     */
    public function testIconvTransliteration($in, $out)
    {
        if (!function_exists('iconv')) {
            $this->markTestSkipped();
        }
        $translit = iconv('utf-8', 'us-ascii//TRANSLIT', $in);
        $this->assertEquals($out, $translit, 'iconv transliteration behaves as expected');
    }

}
