<?php
namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\InstallCorePlugins
 */
class InstallCorePlugins extends \Change\Application\Console\ChangeCommand
{
	/**
	 */
	protected function configure()
	{
		//$name, $shortcut = null, $mode = null, $description = '', $default = null
		$this->setDescription('Initialize CorePlugins')
			->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path of document root', '.');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Install Core Plugins...</info>');
		$path = realpath($input->getOption('path'));
		$pluginManager = $this->getChangeApplicationServices()->getPluginManager();
		$pluginManager->compile();
		$pluginManager->installPackage('change', 'core', array('path' => $path));
	}
}