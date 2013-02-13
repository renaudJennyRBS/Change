<?php
namespace Change\Application\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @name \Change\Application\Console\ChangeCommand
 * @api
 */
class ChangeCommand extends Command
{
	/**
	 * @var \Change\Application
	 */
	protected $changeApplication;

	/**
	 * Get the Change Application instance managed by the console tool
	 *
	 * @api
	 * @throws \RuntimeException
	 * @return \Change\Application
	 */
	public function getChangeApplication()
	{
		if (!($this->changeApplication instanceof \Change\Application))
		{
			throw new \RuntimeException('No Change application Associated with this command');
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
	 * Override to allow command only in developer mode
	 * (DEVELOPMENT_MODE=true or forced with --dev)
	 *
	 * @api
	 * @return boolean
	 */
	public function isDevCommand()
	{
		return false;
	}

	/**
	 * Override this method for complex argument validation.
	 * You always call the parent implementation.
	 *
	 * @api
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws Exception
	 * @return mixed number
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);
		$devMode = $input->getOption('dev') || $this->getChangeApplication()->inDevelopmentMode();
		if ($this->isDevCommand() && !$devMode)
		{
			throw new \RuntimeException("This is a developper command, you can only run it in developer mode");
		}
	}
}