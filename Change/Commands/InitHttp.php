<?php
namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\InitHttp
 */
class InitHttp extends \Change\Application\Console\ChangeCommand
{
	/**
	 */
	protected function configure()
	{
		//$name, $shortcut = null, $mode = null, $description = '', $default = null
		$this->setDescription('Initialize documents root files')
			->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path of document root', '.');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$optionPath = $input->getOption('path');
		$path = realpath($optionPath);
		if (!$path)
		{
			$output->writeln('<info> Path: ' . $optionPath . ' not found</info>');
			return;
		}
		if (!is_writable($path))
		{
			$output->writeln('<info> Path: ' . $path . ' is not writable</info>');
			return;
		}

		$cmd = new \Change\Http\InitHttpFiles($this->getChangeApplication());
		$cmd->initializeControllers($path);
	}
}