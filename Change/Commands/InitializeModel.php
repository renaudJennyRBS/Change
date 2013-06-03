<?php
namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\InitializeModel
 */
class InitializeModel extends \Change\Application\Console\ChangeCommand
{
	/**
	 */
	protected function configure()
	{
		// Configure your command here
		$this->setDescription("Initialize an new XML model file");
		$this->addArgument('vendor', InputArgument::REQUIRED, 'vendor of the target module');
		$this->addArgument('module', InputArgument::REQUIRED, 'name of the target module');
		$this->addArgument('name', InputArgument::REQUIRED, 'short name of the model');

	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path = $this->getChangeDocumentServices()->getModelManager()->initializeModel($input->getArgument('vendor'), $input->getArgument('module'), $input->getArgument('name'));
		$output->writeln('<info>Model definition written at path ' . $path .'</info>');
		$path = $this->getChangeDocumentServices()->getModelManager()->initializeFinalDocumentPhpClass($input->getArgument('vendor'), $input->getArgument('module'), $input->getArgument('name'));
		$output->writeln('<info>Final php document class  written at path ' . $path .'</info>');
	}
}