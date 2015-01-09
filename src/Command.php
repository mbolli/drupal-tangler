<?php

namespace Drupal\Tangler;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    protected function configure()
    {
        $this->setName('drupal:tangle')
            ->setDescription('Tangle code into a working Drupal application')
            ->addArgument(
                'project',
                InputArgument::OPTIONAL,
                'path to project to tangle'
            )
            ->addArgument(
                'drupal',
                InputArgument::OPTIONAL,
                'path to drupal in which to tangle',
                'www'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $projectArg = $input->getArgument('project');
        $drupalArg = $input->getArgument('drupal');
        $project = (!empty($projectArg) && $fs->isAbsolutePath($projectArg)) ?
            $projectArg :
            implode('/', array(getcwd(), $projectArg));
        $drupal = $fs->isAbsolutePath($drupalArg) ?
            $drupalArg :
            implode('/', array(getcwd(), $drupalArg));
        $mapper = new Mapper(
            $this->normalizePath($project),
            $this->normalizePath($drupal)
        );
        $mapper->clear();
        $mapper->mirror($mapper->getMap(
            $this->getApplication()->getComposer(true)->getInstallationManager(),
            $this->getApplication()->getComposer(true)->getRepositoryManager()
        ));
    }

    private function normalizePath($path) {
        $patterns = array('/(\/){2,}/', '/([^\/]+\/\.{2,}\/)|(\.\/)/');
        $replacements = array('/', '');
        return preg_replace($patterns, $replacements, $path);
    }
}
