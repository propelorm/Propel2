<?php

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\PyStringNode;

class FeatureContext extends BehatContext
{
    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * @var string
     */
    protected $platform;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
        $this->workingDirectory = sys_get_temp_dir();
    }

    /**
     * @Given /^a platform is "([^"]*)"$/
     */
    public function aPlatformIs($platform)
    {
        $this->platform = $platform;
    }

    /**
     * @Given /^I have XML schema:$/
     */
    public function iHaveXmlSchema(PyStringNode $string)
    {
        file_put_contents($this->workingDirectory . '/schema.xml', $string->getRaw());
    }

    /**
     * @When /^I generate SQL$/
     */
    public function iGenerateSql()
    {
        exec(sprintf('bin/propel sql:build --platform=%s --input-dir=%s --output-dir=%s',
            $this->platform,
            $this->workingDirectory,
            $this->workingDirectory
        ));
    }

    /**
     * @Then /^it should contain:$/
     */
    public function itShouldContain(PyStringNode $string)
    {
        $sql = file_get_contents($this->workingDirectory . '/behat_table.sql');

        if (empty($sql) || !preg_match(sprintf('/%s/', preg_quote($string->getRaw())), $sql)) {
            $this->printDebug($sql);
            throw new Exception('Content not found');
        }
    }
}
