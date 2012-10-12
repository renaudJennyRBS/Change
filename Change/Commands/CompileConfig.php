<?php

namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompileConfig extends \Change\Application\Console\ChangeCommand
{	
	/**
	 */
	protected function configure()
	{
		$this->setDescription('Compile configuration files')
		->setHelp('This commands compiles the application\'s configuration files into a PHP file.' );
	}
	
	/**
	 *
	 * @param InputInterface $input        	
	 * @param OutputInterface $output        	
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Compiling configuration...</info>');
		$generator = new \Change\Configuration\Generator($this->getChangeApplication());
		$generator->compile();
		$output->writeln('<info>Done !</info>');
	}
}