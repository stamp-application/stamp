<?php

namespace Stamp\Console\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class RawRunCommand extends BaseCommand
{

    protected function configure()
    {
        $this
            ->setName('raw:run')
            ->setDescription('The command to execute raw steps.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Should the command run in dry mode?'
            );
    }

    public function getDefaultValue(InputInterface $input, $name, $default = false)
    {
        $result = $default;
        if ($input->getOption($name)) {
            $result = $input->getOption($name);
        }

        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $this->getDefaultValue($input, 'dry-run');
        $verbose = $this->getDefaultValue($input, 'verbose');

        $container = $this->getApplication()->getContainer();
        $config = $this->getApplication()->getConfig();

        foreach ($config['actions'] as $action) {
            $executor = $container->get('stamp.actions.' . $action['name']);
            $executor->setParams($action['parameters']);
            $executor->setVerbose($verbose);
            $executor->setDryRun($dryRun);
            $executor->exec();
            if ($verbose) {
                $output->writeln($executor->getOutput());
            }
        }
    }
}
