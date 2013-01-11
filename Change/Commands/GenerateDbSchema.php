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
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Generate database Schema...</info>');
		
		$dbp = $this->getChangeApplication()->getApplicationServices()->getDbProvider();
		$schemaManager = $dbp->getSchemaManager();
		
		if (!$schemaManager->check())
		{
			$output->writeln('<info>unable to connect to database: '.$schemaManager->getName().'</info>');
		}
		$relativePath = 'Db' . DIRECTORY_SEPARATOR . ucfirst($dbp->getType()) . DIRECTORY_SEPARATOR . 'Assets';
		
		$workspace = $this->getChangeApplication()->getWorkspace();
		$pattern = $workspace->changePath($relativePath, '*.sql');
		
		$paths = \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT);
		
		if (is_dir($workspace->pluginsModulesPath()))
		{
			$pattern = $workspace->pluginsModulesPath('*', '*', $relativePath, '*.sql');
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
		
		if (is_dir($workspace->projectModulesPath()))
		{
			$pattern = $workspace->projectModulesPath('*', '*', $relativePath, '*.sql');
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
		
		foreach ($paths as $path)
		{
			$sql = file_get_contents($path);
			$output->writeln('<info>generate : ' . $path .'</info>');
			$schemaManager->execute($sql);
		}	
		
		if (class_exists('Compilation\Change\Documents\Schema'))
		{
			$documentSchema = new \Compilation\Change\Documents\Schema();
			foreach ($documentSchema->getTables() as $tableDef)
			{
				/* @var $tableDef \Change\Db\Schema\TableDefinition */
				$schemaManager->createOrAlter($tableDef);
			}
		}
		$output->writeln('<info>generated !</info>');
	}
}