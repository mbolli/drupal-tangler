<?php

namespace Drupal\Tangler;

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
            ->addOption(
                'project',
                'p',
                InputArgument::OPTIONAL,
                'path to project to tangle'
            )
            ->addOption(
                'drupal',
                'd',
                InputArgument::OPTIONAL,
                'path to drupal in which to tangle',
                'www'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mapper = new Mapper(
            implode('/', [getcwd(), $input->getOption('project')]),
            implode('/', [getcwd(), $input->getOption('drupal')])
        );
        $mapper->clear();
        $mapper->mirror($mapper->getMap(
            $this->getApplication()->getComposer(true)->getInstallationManager(),
            $this->getApplication()->getComposer(true)->getRepositoryManager()
        ));
    }
}
