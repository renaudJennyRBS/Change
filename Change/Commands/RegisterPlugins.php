<?php
namespace Change\Commands;

use Change\Application\Console\ChangeCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\RegisterPlugins
 */
class RegisterPlugins extends ChangeCommand
{	
	/**
	 */
	protected function configure()
	{
		$this->setDescription('Register All Plugins');
	}
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Register All Plugins...</info>');
		$pluginManager = $this->getChangeApplicationServices()->getPluginManager();
		$plugins = $pluginManager->getUnregisteredPlugins();
		foreach ($plugins as $plugin)
		{
			$pluginManager->register($plugin);
		}
		$nbPlugins = count($plugins);
		$output->writeln('<info>' .$nbPlugins. ' new plugins added !</info>');

		$plugins = $pluginManager->compile();
		$nbPlugins = count($plugins);
		$output->writeln('<info>' .$nbPlugins. ' registered !</info>');
	}
}