<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommand extends BaseServeCommand
{
    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if (windows_os() && ! $input->getOption('no-reload')) {
            $input->setOption('no-reload', true);
        }

        parent::initialize($input, $output);
    }
}
