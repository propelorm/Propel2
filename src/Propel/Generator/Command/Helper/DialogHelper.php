<?php

namespace Propel\Generator\Command\Helper;

use Symfony\Component\Console\Output\OutputInterface;

class DialogHelper extends \Symfony\Component\Console\Helper\DialogHelper
{
    public function ask(OutputInterface $output, $question, $default = null, array $autocomplete = null)
    {
        return parent::ask($output, $this->formatQuestion($question, $default), $default, $autocomplete);
    }

    public function askHiddenResponse(OutputInterface $output, $question, $fallback = true)
    {
        return parent::askHiddenResponse($output, $this->formatQuestion($question), $fallback);
    }

    public function writeSection(OutputInterface $output, $text)
    {
        $output->writeln([
            '',
            $text,
        ]);
    }

    public function writeBlock(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $formatter = $this->getHelperSet()->get('formatter');
        $block = $formatter->formatBlock($text, $style, true);

        $this->writeSection($output, $block);
    }

    public function writeSummary(OutputInterface $output, $items)
    {
        $output->writeln('');
        foreach ($items as $name => $value) {
            $output->writeln(sprintf('<info>%s</info>: <comment>%s</comment>', $name, $value));
        }
    }

    private function formatQuestion($question, $default = null)
    {
        if ($default) {
            return sprintf('<info>%s</info> [<comment>%s</comment>]: ', $question, $default);
        } else {
            return sprintf('<info>%s</info>: ', $question);
        }
    }

    public function select(OutputInterface $output, $question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid', $multiselect = false)
    {
        return parent::select($output, $this->formatQuestion($question, $default), $choices, $default, $attempts, $errorMessage, $multiselect);
    }

    public function askConfirmation(OutputInterface $output, $question, $default = true)
    {
        return parent::askConfirmation($output, $this->formatQuestion($question, $default ? 'yes' : 'no'), $default);
    }
}
