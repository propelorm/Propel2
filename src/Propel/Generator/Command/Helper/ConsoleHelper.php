<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ConsoleHelper extends QuestionHelper
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param string $questionText
     * @param string|null $default
     * @param array|null $autocomplete
     *
     * @return mixed
     */
    public function askQuestion(string $questionText, ?string $default = null, ?array $autocomplete = null)
    {
        $question = new Question($this->formatQuestion($questionText, $default), $default);
        $question->setAutocompleterValues($autocomplete);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @param string $questionText
     * @param bool $fallback
     *
     * @return mixed
     */
    public function askHiddenResponse(string $questionText, bool $fallback = true)
    {
        $question = new Question($this->formatQuestion($questionText));
        $question->setHidden(true);
        $question->setHiddenFallback($fallback);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @param string $text
     *
     * @return void
     */
    public function writeSection(string $text): void
    {
        $this->output->writeln([
            '',
            $text,
        ]);
    }

    /**
     * @param string $text
     * @param string $style
     *
     * @return void
     */
    public function writeBlock(string $text, string $style = 'bg=blue;fg=white'): void
    {
        /** @var \Symfony\Component\Console\Helper\FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');
        $block = $formatter->formatBlock($text, $style, true);

        $this->writeSection($block);
    }

    /**
     * @param array<string, string> $items
     *
     * @return void
     */
    public function writeSummary(array $items): void
    {
        $this->output->writeln('');
        foreach ($items as $name => $value) {
            $this->output->writeln(sprintf('<info>%s</info>: <comment>%s</comment>', $name, $value));
        }
    }

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
    public function select(
        string $question,
        array $choices,
        ?string $default = null,
        ?int $attempts = null,
        string $errorMessage = 'Value "%s" is invalid',
        bool $multiselect = false
    ) {
        $choiceQuestion = new ChoiceQuestion($this->formatQuestion($question, $default), $choices, $default);

        if ($attempts) {
            $choiceQuestion->setMaxAttempts($attempts);
        }

        $choiceQuestion->setErrorMessage($errorMessage);
        $choiceQuestion->setMultiselect($multiselect);

        return parent::ask($this->input, $this->output, $choiceQuestion);
    }

    /**
     * @param string $questionText
     * @param bool $default
     *
     * @return mixed
     */
    public function askConfirmation(string $questionText, bool $default = true)
    {
        $question = new ConfirmationQuestion($this->formatQuestion($questionText, $default ? 'yes' : 'no'), $default);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return void
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @param iterable|string $messages
     * @param int $options
     *
     * @return void
     */
    public function writeln($messages, int $options = 0): void
    {
        $this->output->writeln($messages, $options);
    }

    /**
     * @param string $question
     * @param string|null $default
     *
     * @return string
     */
    protected function formatQuestion(string $question, ?string $default = null): string
    {
        if ($default) {
            return sprintf('<info>%s</info> [<comment>%s</comment>]: ', $question, $default);
        }

        return sprintf('<info>%s</info>: ', $question);
    }
}
