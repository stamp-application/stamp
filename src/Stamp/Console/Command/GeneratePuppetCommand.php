<?php

namespace Stamp\Console\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class GeneratePuppetCommand extends BaseCommand
{

    protected function configure()
    {
        $this
            ->setName('generate:puppet')
            ->setDescription('The command to create example configuration for puppet module.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (file_exists('stamp.yml')) {
            throw new RuntimeException('The configuration file "stamp.yml" already exists!');
        }

        $puppetTemplate = <<<EOF
# This file was auto-generated by stamp
# For more info visit http://github.com/gajdaw/stamp

filename:    'metadata.json'
regex:       '/"version": "(?P<version>[\d\.]+)",/'
replacement: '"version": "{{ version }}",'
EOF;

        file_put_contents('stamp.yml', $puppetTemplate);
        $output->writeln('The configuration file "stamp.yml" was successfully generated.');
    }

}
