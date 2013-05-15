<?php
namespace Change\Commands;

use Change\Application\Console\ChangeCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\CompileDocuments
 */
class CompilePluginsRegistration extends ChangeCommand
{	
	/**
	 */
	protected function configure()
	{
		$this->setDescription('Compile Plugins Registration');
	}
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Compiling Plugins Registration...</info>');
		$pluginManager = $this->getChangeApplicationServices()->getPluginManager();
		$plugins = $pluginManager->compile();
		$nbPlugins = count($plugins);
		$output->writeln('<info>' .$nbPlugins. ' registered !</info>');
	}
}