<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class HelpCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('help')
            ->setDescription('')
            ->setHelp(<<<EOT
<info>php composer.phar help</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(<<<EOT
<info>Composer - Package Management for PHP</info>
<comment>Composer is a package manager tracking local dependencies of your projects and libraries.
See http://packagist.org/about for more information.</comment>
EOT
        );

    }
}
