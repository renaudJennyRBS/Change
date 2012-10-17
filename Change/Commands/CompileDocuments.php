<?php

namespace Change\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompileDocuments extends \Change\Application\Console\AbstractCommand
{	
	/**
	 */
	protected function configure()
	{
		$this->setDescription('Compile Documents');
	}
	
	/**
	 *
	 * @param InputInterface $input        	
	 * @param OutputInterface $output        	
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/* @var $application \Change\Application\ConsoleApplication */
		$application = $this->getApplication();
		$output->writeln('<info>Compiling Documents...</info>');
		$compiler = new \Change\Documents\Generators\Compiler();
		$paths = array();
		if (is_dir(implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'Plugins', 'Modules'))))
		{
			$pattern = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'Plugins', 'Modules', '*', '*', 'Documents', 'Assets', '*.xml'));
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
		
		if (is_dir(implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'App', 'Modules'))))
		{
			$pattern = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'App', 'Modules', '*', '*', 'Documents', 'Assets', '*.xml'));
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
		
		$nbModels = 0;
		foreach ($paths as $definitionPath)
		{
			$parts = explode(DIRECTORY_SEPARATOR, $definitionPath);
			$count = count($parts);
			$documentName = basename($parts[$count - 1], '.xml');
			$moduleName = $parts[$count - 4];
			$vendor = $parts[$count - 5];
			$compiler->loadDocument($vendor, $moduleName, $documentName, $definitionPath);
			$nbModels++;
		}
		
		$compiler->buildDependencies();
		
		$compiler->saveModelsPHPCode();
		
		$output->writeln('<info>' .$nbModels. ' compiled !</info>');
	}
}