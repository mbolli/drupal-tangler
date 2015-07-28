<?php

namespace Drupal\Tangler;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    protected function configure()
    {
        $this->setName('drupal:tangle')
            ->setDescription('Tangle code into a working Drupal application')
            ->addOption(
                'project',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Path to project to tangle. Default: ./'
            )
            ->addOption(
                'drupal',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Path to drupal in which to tangle. Default: ./www',
                'www'
            )
            ->addOption(
                'copy',
                'c',
                InputOption::VALUE_NONE,
                "Copy files to the project's mapped directories instead of creating symlinks"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $projectArg = $input->getOption('project');
        $drupalArg = $input->getOption('drupal');
        $project = (!empty($projectArg) && $fs->isAbsolutePath($projectArg)) ?
            $projectArg :
            implode('/', [getcwd(), $projectArg]);
        $drupal = $fs->isAbsolutePath($drupalArg) ?
            $drupalArg :
            implode('/', [getcwd(), $drupalArg]);
        $mapper = new Mapper(
            $this->normalizePath($project),
            $this->normalizePath($drupal),
            $input->getOption('copy')
        );
        $mapper->clear();
        $mapper->mirror($mapper->getMap(
            $this->getApplication()->getComposer(true)->getInstallationManager(),
            $this->getApplication()->getComposer(true)->getRepositoryManager()
        ));
    }

    private function normalizePath($path) {
        $patterns = ['/(\/){2,}/', '/([^\/]+\/\.{2,}\/)|(\.\/)/'];
        $replacements = ['/', ''];
        return preg_replace($patterns, $replacements, $path);
    }
}
