<?php

namespace Change\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshConfig extends \Change\Application\Console\ChangeCommand
{
	/**
	 */
	protected function configure()
	{
		$this->setDescription('Refresh configuration informations')
		->setHelp('This command should be called each time the configuration files are modified.' );
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Refreshing configuration...</info>');
		$config = new \Change\Configuration\Configuration($this->getChangeApplication());
		$config->refresh();
		$output->writeln('<info>Refreshed!</info>');
	}
}