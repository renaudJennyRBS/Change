<?php
namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Json\Json;

/**
 * @name \Change\Commands\InitializePlugin
 */
class InitializePlugin extends \Change\Application\Console\ChangeCommand
{

	/**
	 */
	protected function configure()
	{
		// Configure your command here
		$this->setDescription("Initialize an new empty plugin skeleton");
		$this->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'type of plugin', 'module');
		$this->addOption('vendor', 'e', InputOption::VALUE_OPTIONAL, 'vendor of the plugin', 'project');
		$this->addArgument('name', InputArgument::REQUIRED, 'short name of the plugin');
	}

	/**
	 * @return bool
	 */
	public function isDevCommand()
	{
		return true;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path = $this->getChangeApplicationServices()->getPluginManager()->initializePlugin($input->getOption('type'), $input->getOption('vendor'), $input->getArgument('name'));
		$output->writeln('<info>Plugin skeleton generated at ' . $path.  '</info>');
	}
}