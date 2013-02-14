<?php
namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\CompileDocuments
 */
class CompileDocuments extends \Change\Application\Console\ChangeCommand
{	
	/**
	 */
	protected function configure()
	{
		$this->setDescription('Compile Documents');
	}
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Compiling Documents...</info>');
		$compiler = new \Change\Documents\Generators\Compiler($this->getChangeApplication(), $this->getChangeApplicationServices());
		$compiler->generate();
		$nbModels = count($compiler->getModels());
		$output->writeln('<info>' .$nbModels. ' compiled !</info>');
	}
}