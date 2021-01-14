<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command\Helper;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleHelperInterface
{
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output);

    /**
     * @param string $question
     * @param string|null $default
     * @param array|null $autocomplete
     *
     * @return mixed
     */
    public function askQuestion($question, $default = null, ?array $autocomplete = null);

    /**
     * @param string $question
     * @param bool $fallback
     *
     * @return mixed
     */
    public function askHiddenResponse($question, $fallback = true);

    /**
     * @param string $text
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

    /**
     * @param string[] $items
     *
     * @return void
     */
    public function writeSummary($items);

    /**
     * @param string $question
     * @param array $choices
     * @param string|null $default
     * @param int|null $attempts
     * @param string $errorMessage
     * @param bool $multiselect
     *
     * @return mixed
     */
    public function select($question, $choices, $default = null, $attempts = null, $errorMessage = 'Value "%s" is invalid', $multiselect = false);

    /**
     * @param string $question
     * @param bool $default
     *
     * @return mixed
     */
    public function askConfirmation($question, $default = true);

    /**
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput();

    /**
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    public function getInput();

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function setOutput(OutputInterface $output);

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return void
     */
    public function setInput(InputInterface $input);

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     *
     * @return void
     */
    public function writeln($messages, $options = 0);
}
