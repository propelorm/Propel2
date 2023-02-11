<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends SymfonyApplication
{
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        if (extension_loaded('xdebug')) {
            $output->writeln(
                '<comment>You are running propel with xdebug enabled. This has a major impact on runtime performance.</comment>' . "\n",
            );
        }

        return parent::doRun($input, $output);
    }
}
