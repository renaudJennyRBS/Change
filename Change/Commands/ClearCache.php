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
class ClearCache extends \Change\Application\Console\ChangeCommand
{
	/**
	 */
	protected function configure()
	{
		//$name, $shortcut = null, $mode = null, $description = '', $default = null
		$this->setDescription('Clear Project File Cache');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		\Change\Stdlib\File::rmdir($this->getChangeApplication()->getWorkspace()->cachePath(), true);
		$output->writeln('<info>Done!</info>');
	}
}