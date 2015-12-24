<?php

namespace Propel\Generator\Command\Helper;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleHelperInterface
{
    public function __construct(InputInterface $input, OutputInterface $output);

    /**
     * @param string        $question
     * @param string|null   $default
     * @param array|null    $autocomplete
     *
     * @return mixed
     */
    public function askQuestion($question, $default = null, array $autocomplete = null);

    /**
     * @param string $question
     * @param bool $fallback
     *
     * @return mixed
     */
    public function askHiddenResponse($question, $fallback = true);

    /**
     * @param $text
     *
     * @return mixed
     */
    public function writeSection($text);

    /**
     * @param string $text
     * @param string $style
     *
     * @return mixed
     */
    public function writeBlock($text, $style = 'info');

    public function writeSummary($items);

    /**
     * @param string $question
     * @param array  $choices
     * @param string $default
     * @param bool   $attempts
     * @param string $errorMessage
     * @param bool   $multiselect
     *
     * @return mixed
     */
    public function select($question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid', $multiselect = false);

    /**
     * @param string    $question
     * @param bool      $default
     *
     * @return mixed
     */
    public function askConfirmation($question, $default = true);

    /**
     * @return OutputInterface
     */
    public function getOutput();

    /**
     * @return InputInterface
     */
    public function getInput();

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output);

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input);

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int          $options  A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function writeln($messages, $options = 0);
}
