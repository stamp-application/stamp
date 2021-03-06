<?php

namespace Stamp\Console\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use RuntimeException;

abstract class BaseIncreaseVersionCommand extends BaseCommand
{
    protected $config;
    protected $container;
    protected $verbose;
    protected $dry_run;

    public function validateConfig()
    {
        if ($this->config === null) {
            throw new RuntimeException('Configuration file not found!');
        }

        $keys = array_keys($this->config);

        if (!in_array('filename', $keys)) {
            throw new RuntimeException('Cannot find "filename" entry in config file!');
        }
        if (!in_array('regex', $keys)) {
            throw new RuntimeException('Cannot find "regex" entry in config file!');
        }
        if (!in_array('replacement', $keys)) {
            throw new RuntimeException('Cannot find "replacement" entry in config file!');
        }

        if (!file_exists($this->config['filename'])) {
            throw new RuntimeException(sprintf(
                'The file "%s" does not exist!',
                $this->config['filename']
            ));
        }

        try {
            $resultOfMatching = preg_match($this->config['regex'], 'some text');
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Error during parsing regex "%s" from config file!', $this->config['regex']));
        }

    }

    public function addGenericOptions()
    {
        $this
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

    protected function getPreActions()
    {
        return array(
            array(
                'name' => 'parse_variable_from_file',
                'parameters' => array(
                    'filename' => $this->config['filename'],
                    'regex' => $this->config['regex'],
                )
            )
        );
    }

    protected function getActions()
    {
        return array();
    }

    protected function getSaveActions()
    {
        $array = array(
            array(
                'name' => 'save_variable_to_file',
                'parameters' => array(
                    'filename' => $this->config['filename'],
                    'variable' => $this->config['variable'],
                    'src'      => $this->config['regex'],
                    'dest'     => $this->config['replacement'],
                )
            )
        );

        if (isset($this->config['replacements'])) {
            foreach ($this->config['replacements'] as $replacement) {
                $array[] = array(
                    'name' => 'save_variable_to_file',
                    'parameters' => array(
                        'filename' => $replacement['filename'],
                        'variable' => $this->config['variable'],
                        'src'      => $replacement['regex'],
                        'dest'     => $replacement['replacement'],
                    )
                );
            }
        }

        return $array;
    }

    protected function getGitAddActions()
    {
        $array = array(
            array(
                'name' => 'command',
                'parameters' => array(
                    'commandTemplate' => 'git add ' . $this->config['filename'],
                )
            )
        );

        if (isset($this->config['replacements'])) {
            foreach ($this->config['replacements'] as $replacement) {
                $array[] = array(
                    'name' => 'command',
                    'parameters' => array(
                        'commandTemplate' => 'git add ' . $replacement['filename'],
                    )
                );
            }
        }

        return $array;
    }

    protected function getPostActions()
    {
        return array(
            array(
                'name' => 'command',
                'parameters' => array(
                    'commandTemplate' => 'git commit -m "Version {{ version }}"',
                )
            ),
            array(
                'name' => 'command',
                'parameters' => array(
                    'commandTemplate' => 'git tag -a v{{ version }} -m "Release {{ version }}"',
                )
            )
        );
    }

    public function runActions($actions, OutputInterface $output)
    {
        foreach ($actions as $action) {
            $executor = $this->container->get('stamp.actions.' . $action['name']);
            $executor->setParams($action['parameters']);
            $executor->setVerbose($this->verbose);
            $executor->setDryRun($this->dryRun);
            $executor->exec();
            if ($this->verbose) {
                $output->writeln($executor->getOutput());
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dryRun = $this->getDefaultValue($input, 'dry-run');
        $this->verbose = $this->getDefaultValue($input, 'verbose');

        $this->container = $this->getApplication()->getContainer();
        $this->config = $this->getApplication()->getConfig();

        $this->validateConfig();

        $this->runActions($this->getPreActions(), $output);
        $this->autoSetVariable();
        $this->runActions($this->getActions(), $output);
        $this->runActions($this->getSaveActions(), $output);
        $this->runActions($this->getGitAddActions(), $output);
        $this->runActions($this->getPostActions(), $output);
    }

    public function autoSetVariable()
    {
        $variableContainer = $this->container->get('stamp.actions.variable_container');
        $this->config['variable'] = $variableContainer->getFirstVariableName();
    }
}
