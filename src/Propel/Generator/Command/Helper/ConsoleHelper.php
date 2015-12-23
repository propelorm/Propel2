<?php

namespace Propel\Generator\Command\Helper;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DialogHelper as Symfony23DialogHelper;

class ConsoleHelper extends Symfony23DialogHelper implements ConsoleHelperInterface
{
    protected $input;
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
        return parent::ask($this->output, $this->formatQuestion($question, $default), $default, $autocomplete);
    }

    /**
     * @inheritdoc
     */
    public function askHiddenResponse($question, $fallback = true)
    {
        return parent::askHiddenResponse($this->output, $this->formatQuestion($question), $fallback);
    }

    /**
     * @inheritdoc
     */
    public function writeSection($text)
    {
        $this->writeln([
            '',
            $text,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function writeBlock($text, $style = 'bg=blue;fg=white')
    {
        $formatter = $this->getHelperSet()->get('formatter');
        $block = $formatter->formatBlock($text, $style, true);

        $this->writeSection($block);
    }

    /**
     * @inheritdoc
     */
    public function writeSummary($items)
    {
        $this->writeln('');
        foreach ($items as $name => $value) {
            $this->writeln(sprintf('<info>%s</info>: <comment>%s</comment>', $name, $value));
        }
    }

    /**
     * @inheritdoc
     */
    private function formatQuestion($question, $default = null)
    {
        if ($default) {
            return sprintf('<info>%s</info> [<comment>%s</comment>]: ', $question, $default);
        } else {
            return sprintf('<info>%s</info>: ', $question);
        }
    }

    /**
     * @inheritdoc
     */
    public function select($question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid', $multiselect = false)
    {
        return parent::select(
                                $this->output,
                                $this->formatQuestion($question, $default),
                                $choices,
                                $default,
                                $attempts,
                                $errorMessage,
                                $multiselect
                             );
    }

    /**
     * @inheritdoc
     */
    public function askConfirmation($question, $default = true)
    {
        return parent::askConfirmation($this->output, $this->formatQuestion($question, $default ? 'yes' : 'no'), $default);
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
        $this->output->writeln($messages, $options);
    }
}
