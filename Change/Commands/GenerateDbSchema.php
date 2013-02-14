<?php
namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Commands\GenerateDbSchema
 */
class GenerateDbSchema extends \Change\Application\Console\ChangeCommand
{	
	/**
	 */
	protected function configure()
	{
		$this->setDescription('Generate database Schema');
	}
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$generator = new \Change\Db\Schema\Generator($this->getChangeApplication()->getWorkspace(), $this->getChangeApplicationServices()->getDbProvider());
		try 
		{
			$output->writeln('<info>Generate database Schema...</info>');
			$generator->generate();
			$output->writeln('<info>generated !</info>');
		} 
		catch (\Exception $e )
		{
			$output->writeln('<info>'. $e->getMessage().'</info>');
		}
	}
}