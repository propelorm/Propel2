<?php

namespace Propel\Generator\Command\Helper;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ConsoleHelper3 extends QuestionHelper implements ConsoleHelperInterface
{
    /** @var InputInterface $input */
    protected $input;

    /** @var OutputInterface $output */
    protected $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @inheritdoc
     */
    public function askQuestion($question, $default = null, array $autocomplete = null)
    {
        $question = new Question($this->formatQuestion($question, $default), $default);
        $question->setAutocompleterValues($autocomplete);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @inheritdoc
     */
    public function askHiddenResponse($question, $fallback = true)
    {
        $question = new Question($this->formatQuestion($question));
        $question->setHidden(true);
        $question->setHiddenFallback($fallback);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @inheritdoc
     */
    public function writeSection($text)
    {
        $this->output->writeln([
            '',
            $text,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function writeBlock($text, $style = 'bg=blue;fg=white')
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');
        $block = $formatter->formatBlock($text, $style, true);

        $this->writeSection($block);
    }

    /**
     * @inheritdoc
     */
    public function writeSummary($items)
    {
        $this->output->writeln('');
        foreach ($items as $name => $value) {
            $this->output->writeln(sprintf('<info>%s</info>: <comment>%s</comment>', $name, $value));
        }
    }

    /**
     * @inheritdoc
     */
    public function select($question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid', $multiselect = false)
    {
        $question = new ChoiceQuestion($this->formatQuestion($question, $default), $choices, $default);

        if ($attempts) {
            $question->setMaxAttempts($attempts);
        }

        $question->setErrorMessage($errorMessage);
        $question->setMultiselect($multiselect);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @inheritdoc
     */
    public function askConfirmation($question, $default = true)
    {
        $question = new ConfirmationQuestion($this->formatQuestion($question, $default ? 'yes' : 'no'), $default);

        return parent::ask($this->input, $this->output, $question);
    }

    /**
     * @inheritdoc
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @inheritdoc
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @inheritdoc
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @inheritdoc
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @inheritdoc
     */
    public function writeln($messages, $options = 0)
    {
        return $this->output->writeln($messages, $options);
    }

    /**
     * @param string        $question
     * @param string|null   $default
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
