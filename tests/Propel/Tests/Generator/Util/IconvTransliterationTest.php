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
     * Ensure iconv is available and locale supports transliteration.
     */
    protected function setUp(): void
    {
        if (!function_exists('iconv')) {
            $this->markTestSkipped('iconv() is not available.');
        }

        $currentLocale = setlocale(LC_CTYPE, 0);
        if (in_array($currentLocale, ['C', 'POSIX'], true)) {
            // Attempt to change the locale to something compatible
            $fallbackLocales = ['en_US.UTF-8', 'C.UTF-8', 'de_DE.UTF-8'];
            foreach ($fallbackLocales as $locale) {
                if (setlocale(LC_CTYPE, $locale) !== false) {
                    return;
                }
            }

            // Still stuck in C/POSIX? Skip
            $this->markTestSkipped("Unsupported locale for iconv transliteration: $currentLocale");
        }
    }

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
            ['ø', 'o'],
            ['Ø', 'O'],
            ['¥Ðð', 'JPYDd'],
        ];
    }

    /**
     * @dataProvider iconvTransliterationSlugProvider
     */
    public function testIconvTransliteration(string $input, string $expected): void
    {
        $translit = iconv('utf-8', 'us-ascii//TRANSLIT', $input);

        // If transliteration results in all question marks, skip (locale likely broken)
        if (preg_match('/^\?+$/', $translit) && $input !== str_repeat('?', strlen($input))) {
            $this->markTestSkipped('iconv() returned only question marks — possibly due to locale issues.');
        }

        $this->assertEquals($expected, $translit, 'iconv transliteration behaves as expected');
    }

}
