<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command\Helper;

use Symfony\Component\Console\Helper\DialogHelper as Symfony23DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleHelper extends Symfony23DialogHelper implements ConsoleHelperInterface
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
     * @inheritDoc
     */
    public function askQuestion($question, $default = null, ?array $autocomplete = null)
    {
        return parent::ask($this->output, $this->formatQuestion($question, $default), $default, $autocomplete);
    }

    /**
     * @inheritDoc
     */
    public function askHiddenResponse($question, $fallback = true)
    {
        return parent::askHiddenResponse($this->output, $this->formatQuestion($question), $fallback);
    }

    /**
     * @inheritDoc
     */
    public function writeSection($text)
    {
        $this->writeln([
            '',
            $text,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function writeBlock($text, $style = 'bg=blue;fg=white')
    {
        $formatter = $this->getHelperSet()->get('formatter');
        $block = $formatter->formatBlock($text, $style, true);

        $this->writeSection($block);
    }

    /**
     * @inheritDoc
     */
    public function writeSummary($items)
    {
        $this->writeln('');
        foreach ($items as $name => $value) {
            $this->writeln(sprintf('<info>%s</info>: <comment>%s</comment>', $name, $value));
        }
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function select($question, $choices, $default = null, $attempts = null, $errorMessage = 'Value "%s" is invalid', $multiselect = false)
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
     * @inheritDoc
     */
    public function askConfirmation($question, $default = true)
    {
        return parent::askConfirmation($this->output, $this->formatQuestion($question, $default ? 'yes' : 'no'), $default);
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
}
