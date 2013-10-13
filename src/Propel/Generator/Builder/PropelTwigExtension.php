<?php


namespace Propel\Generator\Builder;


use Twig_Environment;

class PropelTwigExtension implements \Twig_ExtensionInterface {

    /**
     * Initializes the runtime environment.
     *
     * This is where you can load some file that contains filter functions for instance.
     *
     * @param Twig_Environment $environment The current Twig_Environment instance
     */
    public function initRuntime(Twig_Environment $environment)
    {
    }

    /**
     * Returns the token parser instances to add to the existing list.
     *
     * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
     */
    public function getTokenParsers()
    {

    }

    /**
     * Returns the node visitor instances to add to the existing list.
     *
     * @return array An array of Twig_NodeVisitorInterface instances
     */
    public function getNodeVisitors()
    {

    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('addSlashes', 'addslashes'),
            new \Twig_SimpleFilter('lcfirst', 'lcfirst'),
            new \Twig_SimpleFilter('ucfirst', 'ucfirst'),
            new \Twig_SimpleFilter('indent', function ($string) {
                $lines = explode(PHP_EOL, $string);
                $output = '';

                foreach ($lines as $line) {
                    $output .= '    ' . $line . PHP_EOL;
                }

                return $output;
            }),
            new \Twig_SimpleFilter('varExport', function ($input) {
                return var_export($input, true);
            }),
        ];
    }

    /**
     * Returns a list of tests to add to the existing list.
     *
     * @return array An array of tests
     */
    public function getTests()
    {

    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {

    }

    /**
     * Returns a list of operators to add to the existing list.
     *
     * @return array An array of operators
     */
    public function getOperators()
    {

    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array An array of global variables
     */
    public function getGlobals()
    {

    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'propel';
    }
}