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

class ConsoleHelper3 extends QuestionHelper implements ConsoleHelperInterface
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface $input
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface $output
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
     * @inheritDoc
     */
    public function askQuestion($question, $default = null, ?array $autocomplete = null)
    {
        $question = new Question($this->formatQuestion($question, $default), $default);
        $question->setAutocompleterValues($autocomplete);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @inheritDoc
     */
    public function askHiddenResponse($question, $fallback = true)
    {
        $question = new Question($this->formatQuestion($question));
        $question->setHidden(true);
        $question->setHiddenFallback($fallback);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @inheritDoc
     */
    public function writeSection($text)
    {
        $this->output->writeln([
            '',
            $text,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function writeBlock($text, $style = 'bg=blue;fg=white')
    {
        /** @var \Symfony\Component\Console\Helper\FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');
        $block = $formatter->formatBlock($text, $style, true);

        $this->writeSection($block);
    }

    /**
     * @inheritDoc
     */
    public function writeSummary($items)
    {
        $this->output->writeln('');
        foreach ($items as $name => $value) {
            $this->output->writeln(sprintf('<info>%s</info>: <comment>%s</comment>', $name, $value));
        }
    }

    /**
     * @inheritDoc
     */
    public function select($question, $choices, $default = null, $attempts = null, $errorMessage = 'Value "%s" is invalid', $multiselect = false)
    {
        $choiceQuestion = new ChoiceQuestion($this->formatQuestion($question, $default), $choices, $default);

        if ($attempts) {
            $choiceQuestion->setMaxAttempts($attempts);
        }

        $choiceQuestion->setErrorMessage($errorMessage);
        $choiceQuestion->setMultiselect($multiselect);

        return parent::ask($this->input, $this->output, $choiceQuestion);
    }

    /**
     * @inheritDoc
     */
    public function askConfirmation($question, $default = true)
    {
        $question = new ConfirmationQuestion($this->formatQuestion($question, $default ? 'yes' : 'no'), $default);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @inheritDoc
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @inheritDoc
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @inheritDoc
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @inheritDoc
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @inheritDoc
     */
    public function writeln($messages, $options = 0)
    {
        $this->output->writeln($messages, $options);
    }

    /**
     * @param string $question
     * @param string|null $default
     *
     * @return string
     */
    private function formatQuestion($question, $default = null)
    {
        if ($default) {
            return sprintf('<info>%s</info> [<comment>%s</comment>]: ', $question, $default);
        } else {
            return sprintf('<info>%s</info>: ', $question);
        }
    }
}
