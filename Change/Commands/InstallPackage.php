<?php
namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\InstallPackage
 */
class InstallPackage extends \Change\Application\Console\ChangeCommand
{
	/**
	 */
	protected function configure()
	{
		$this->setDescription("Install a Package");
		$this->addOption('vendor', 'e', InputOption::VALUE_OPTIONAL, 'vendor of the package', 'Project');
		$this->addArgument('name', InputArgument::REQUIRED, 'short name of the package');
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

		$plugins = $pluginManager->installPackage($input->getOption('vendor'), $input->getArgument('name'), array());

		foreach ($plugins as $plugin)
		{
			$output->writeln('<info>' . $plugin.  ' Installed</info>');
		}
	}
}