<?php
namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\InstallPlugin
 */
class InstallPlugin extends \Change\Application\Console\ChangeCommand
{
	/**
	 */
	protected function configure()
	{
		// Configure your command here
		$this->setDescription("Install a plugin");
		$this->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'type of plugin', 'module');
		$this->addOption('vendor', 'e', InputOption::VALUE_OPTIONAL, 'vendor of the plugin', 'Project');
		$this->addArgument('name', InputArgument::REQUIRED, 'short name of the plugin');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$pluginManager = $this->getChangeApplicationServices()->getPluginManager();
		$pluginManager->compile();

		$plugins = $pluginManager->installPlugin($input->getOption('type'), $input->getOption('vendor'), $input->getArgument('name'), array());
		foreach ($plugins as $plugin)
		{
			$output->writeln('<info>' . $plugin.  ' Installed</info>');
		}
	}
}