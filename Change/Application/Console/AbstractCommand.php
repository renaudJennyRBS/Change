<?php

namespace Change\Application\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
	/**
	 * @var \Change\Application
	 */
	protected $changeApplication;
	
	/**
	 * @throws \RuntimeException
	 * @return \Change\Application
	 */
	public function getChangeApplication()
	{
		if (!($this->changeApplication instanceof \Change\Application))
		{
			throw new \RuntimeException('No Change Application Associated with this command');
		}
		return $this->changeApplication;
	}
	
	/**
	 * @param \Change\Application $app
	 */
	public function setChangeApplication(\Change\Application $app)
	{
		$this->changeApplication = $app;
	}
	
	/**
	 * @return boolean
	 */
	public function isDevCommand()
	{
		return false;
	}
	
	/**
	 * @param InputInterface $input        	
	 * @param OutputInterface $output        	
	 * @throws Exception
	 * @return mixed number
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$devMode = $input->getOption('dev') || $this->getChangeApplication()->inDevelopmentMode();
		if ($this->isDevCommand() && !$devMode)
		{
			throw new \RuntimeException("This is a developper command, you can only run it in developer mode");
		}
	}
}