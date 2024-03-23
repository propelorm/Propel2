<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Common\Config\Loader;

use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\RuntimeException;
use Propel\Common\Config\Loader\FileLoader as BaseFileLoader;
use Propel\Tests\TestCase;

class FileLoaderTest extends TestCase
{
    /** @var TestableFileLoader */
    private $loader;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->loader = new TestableFileLoader();
    }

    public function resolveParamsProvider()
    {
        return [
            [
                ['foo'],
                ['foo'],
                '->resolve() returns its argument unmodified if no placeholders are found',
            ],
            [
                ['foo' => 'bar', 'I\'m a %foo%'],
                ['foo' => 'bar', 'I\'m a bar'],
                '->resolve() replaces placeholders by their values',
            ],
            [
                ['foo' => 'bar', '%foo%' => '%foo%'],
                ['foo' => 'bar', 'bar' => 'bar'],
                '->resolve() replaces placeholders in keys and values of arrays',
            ],
            [
                ['foo' => 'bar', '%foo%' => ['%foo%' => ['%foo%' => '%foo%']]],
                ['foo' => 'bar', 'bar' => ['bar' => ['bar' => 'bar']]],
                '->resolve() replaces placeholders in nested arrays',
            ],
            [
                ['foo' => 'bar', 'I\'m a %%foo%%'],
                ['foo' => 'bar', 'I\'m a %foo%'],
                '->resolve() supports % escaping by doubling it',
            ],
            [
                ['foo' => 'bar', 'I\'m a %foo% %%foo %foo%'],
                ['foo' => 'bar', 'I\'m a bar %foo bar'],
                '->resolve() supports % escaping by doubling it',
            ],
            [
                ['foo' => ['bar' => ['ding' => 'I\'m a bar %%foo %%bar']]],
                ['foo' => ['bar' => ['ding' => 'I\'m a bar %foo %bar']]],
                '->resolve() supports % escaping by doubling it',
            ],
            [
                ['foo' => 'bar', 'baz' => '%%%foo% %foo%%% %%foo%% %%%foo%%%'],
                ['foo' => 'bar', 'baz' => '%bar bar% %foo% %bar%'],
                '->resolve() replaces params placed besides escaped %',
            ],
            [
                ['baz' => '%%s?%%s', '%baz%'],
                ['baz' => '%s?%s', '%s?%s'],
                '->resolve() is not replacing greedily',
            ],
            [
                ['host' => 'foo.bar', 'port' => 1337, '%host%:%port%'],
                ['host' => 'foo.bar', 'port' => 1337, 'foo.bar:1337'],
                '',
            ],
            [
                ['foo' => 'bar', '%foo%'],
                ['foo' => 'bar', 'bar'],
                'Parameters must be wrapped by %.',
            ],
            [
                ['foo' => 'bar', '% foo %'],
                ['foo' => 'bar', '% foo %'],
                'Parameters should not have spaces.',
            ],
            [
                ['foo' => 'bar', '{% set my_template = "foo" %}'],
                ['foo' => 'bar', '{% set my_template = "foo" %}'],
                'Twig-like strings are not parameters.',
            ],
            [
                ['foo' => 'bar', '50% is less than 100%'],
                ['foo' => 'bar', '50% is less than 100%'],
                'Text between % signs is allowed, if there are spaces.',
            ],
            [
                ['foo' => ['bar' => 'baz', '%bar%' => 'babar'], 'babaz' => '%foo%'],
                ['foo' => ['bar' => 'baz', 'baz' => 'babar'], 'babaz' => ['bar' => 'baz', 'baz' => 'babar']],
                '',
            ],
            [
                ['foo' => ['bar' => 'baz'], 'babaz' => '%foo%'],
                ['foo' => ['bar' => 'baz'], 'babaz' => ['bar' => 'baz']],
                '',
            ],
        ];
    }

    /**
     * @return void
     */
    public function testResolveParams()
    {
        putenv('host=127.0.0.1');
        putenv('user=root');

        $config = [
            'HoMe' => 'myHome',
            'project' => 'myProject',
            'subhome' => '%HoMe%/subhome',
            'property1' => 1,
            'property2' => false,
            'direcories' => [
                'project' => '%HoMe%/projects/%project%',
                'conf' => '%project%',
                'schema' => '%project%/schema',
                'template' => '%HoMe%/templates',
                'output%project%' => '/build',
            ],
            '%HoMe%' => 4,
            'host' => '%env.host%',
            'user' => '%env.user%',
        ];

        $expected = [
            'HoMe' => 'myHome',
            'project' => 'myProject',
            'subhome' => 'myHome/subhome',
            'property1' => 1,
            'property2' => false,
            'direcories' => [
                'project' => 'myHome/projects/myProject',
                'conf' => 'myProject',
                'schema' => 'myProject/schema',
                'template' => 'myHome/templates',
                'outputmyProject' => '/build',
            ],
            'myHome' => 4,
            'host' => '127.0.0.1',
            'user' => 'root',
        ];

        $this->assertEquals($expected, $this->loader->resolveParams($config));

        //cleanup environment
        putenv('host');
        putenv('user');
    }

    /**
     * @dataProvider resolveParamsProvider
     *
     * @return void
     */
    public function testResolveValues($conf, $expected, $message)
    {
        $this->assertEquals($expected, $this->loader->resolveParams($conf), $message);
    }

    /**
     * @return void
     */
    public function testResolveReplaceWithoutCasting()
    {
        $conf = $this->loader->resolveParams(['foo' => true, 'expfoo' => '%foo%', 'bar' => null, 'expbar' => '%bar%']);

        $this->assertTrue($conf['expfoo'], '->resolve() replaces arguments that are just a placeholder by their value without casting them to strings');
        $this->assertNull($conf['expbar'], '->resolve() replaces arguments that are just a placeholder by their value without casting them to strings');
    }

    /**
     * @return void
     */
    public function testResolveThrowsExceptionIfInvalidPlaceholder()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Parameter 'baz' not found in configuration file.");

        $this->loader->resolveParams(['foo' => 'bar', '%baz%']);
    }

    /**
     * @return void
     */
    public function testResolveThrowsExceptionIfNonExistentParameter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Parameter 'foobar' not found in configuration file.");

        $this->loader->resolveParams(['foo %foobar% bar']);
    }

    /**
     * @return void
     */
    public function testResolveThrowsRuntimeExceptionIfCircularReference()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Circular reference detected for parameter 'bar'.");

        $this->loader->resolveParams(['foo' => '%bar%', 'bar' => '%foobar%', 'foobar' => '%foo%']);
    }

    /**
     * @return void
     */
    public function testResolveThrowsRuntimeExceptionIfCircularReferenceMixed()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Circular reference detected for parameter 'bar'.");

        $this->loader->resolveParams(['foo' => 'a %bar%', 'bar' => 'a %foobar%', 'foobar' => 'a %foo%']);
    }

    /**
     * @return void
     */
    public function testResolveEnvironmentVariable()
    {
        putenv('home=myHome');
        putenv('schema=mySchema');
        putenv('isBoolean=true');
        putenv('integer=1');

        $config = [
            'home' => '%env.home%',
            'property1' => '%env.integer%',
            'property2' => '%env.isBoolean%',
            'direcories' => [
                'projects' => '%home%/projects',
                'schema' => '%env.schema%',
                'template' => '%home%/templates',
                'output%env.home%' => '/build',
            ],
        ];

        $expected = [
            'home' => 'myHome',
            'property1' => '1',
            'property2' => 'true',
            'direcories' => [
                'projects' => 'myHome/projects',
                'schema' => 'mySchema',
                'template' => 'myHome/templates',
                'outputmyHome' => '/build',
            ],
        ];

        $this->assertEquals($expected, $this->loader->resolveParams($config));

        //cleanup environment
        putenv('home');
        putenv('schema');
        putenv('isBoolean');
        putenv('integer');
    }

    /**
     * @return void
     */
    public function testResolveEmptyEnvironmentVariable()
    {
        putenv('home=');

        $config = [
            'home' => '%env.home%',
        ];

        $expected = [
            'home' => '',
        ];

        $this->assertEquals($expected, $this->loader->resolveParams($config));

        //cleanup environment
        putenv('home');
    }

    /**
     * @return void
     */
    public function testResourceNameIsNotStringReturnsFalse()
    {
        $this->assertFalse(TestableFileLoader::checkSupports('ini', null));
        $this->assertFalse(TestableFileLoader::checkSupports('yaml', false));
    }

    /**
     * @return void
     */
    public function testExtensionIsNotStringOrArrayReturnsFalse()
    {
        $this->assertFalse(TestableFileLoader::checkSupports('', '/tmp/propel.yaml'));
        $this->assertFalse(TestableFileLoader::checkSupports('12', '/tmp/propel.yaml'));
    }

    /**
     * @return void
     */
    public function testNonExistentEnvironmentVariableThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Environment variable 'foo' is not defined.");

        putenv('home=myHome');

        $config = [
            'home' => '%env.home%',
            'property1' => '%env.foo%',
        ];

        $this->loader->resolveParams($config);
    }

    /**
     * @return void
     */
    public function testParameterIsNotStringOrNumber()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A string value must be composed of strings and/or numbers,');

        $config = [
            'foo' => 'a %bar%',
            'bar' => [],
            'baz' => '%foo%',
        ];

        $this->loader->resolveParams($config);
    }

    /**
     * @return void
     */
    public function testCallResolveParamTwiceReturnsEmpty()
    {
        $config = [
            'foo' => 'bar',
            'baz' => '%foo%',
        ];

        $this->assertEquals(['foo' => 'bar', 'baz' => 'bar'], $this->loader->resolveParams($config));
        $this->assertSame([], $this->loader->resolveParams($config));
    }
}

class TestableFileLoader extends BaseFileLoader
{
    public function load($resource, $type = null): ?array
    {
        return null;
    }

    public function supports($resource, $type = null): bool
    {
        return false;
    }

    /**
     * @param string|string[] $ext
     * @param mixed $resource
     *
     * @return bool
     */
    public static function checkSupports($ext, $resource): bool
    {
        return parent::checkSupports($ext, $resource);
    }
}
