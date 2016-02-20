<?php

namespace Propel\Generator;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Symfony\Component\Console\Application
{
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (extension_loaded('xdebug')) {
            $output->writeln(
                '<comment>You are running propel with xdebug enabled. This has a major impact on runtime performance.</comment>'."\n"
            );
        }
        return parent::doRun($input, $output);
    }
}