<?php
namespace Change\Application\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @api
 * @name \Change\Application\Console\ChangeCommand
 */
class ChangeCommand extends Command
{
	/**
	 * @var \Change\Application
	 */
	protected $changeApplication;

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $changeApplicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $changeDocumentServices;

	/**
	 * @param \Change\Application $application
	 */
	public function setChangeApplication(\Change\Application $application)
	{
		$this->changeApplication = $application;
	}

	/**
	 * Get the Change Application instance managed by the console tool
	 * @api
	 * @throws \RuntimeException
	 * @return \Change\Application
	 */
	public function getChangeApplication()
	{
		if (!($this->changeApplication instanceof \Change\Application))
		{
			throw new \RuntimeException('No Change application Associated with this command', 10000);
		}
		return $this->changeApplication;
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function setChangeApplicationServices(\Change\Application\ApplicationServices $applicationServices)
	{
		$this->changeApplicationServices = $applicationServices;
	}

	/**
	 * @api
	 * @return \Change\Application\ApplicationServices
	 */
	public function getChangeApplicationServices()
	{
		if ($this->changeApplicationServices === null)
		{
			$this->changeApplicationServices = new \Change\Application\ApplicationServices($this->getChangeApplication());
		}
		return $this->changeApplicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function setChangeDocumentServices(\Change\Documents\DocumentServices $documentServices)
	{
		$this->changeDocumentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function getChangeDocumentServices()
	{
		if ($this->changeDocumentServices === null)
		{
			$this->changeDocumentServices =  new \Change\Documents\DocumentServices($this->getChangeApplicationServices());
		}
		return $this->changeDocumentServices;
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
	 * @api
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \RuntimeException
	 * @return mixed number
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);
		$devMode = $input->getOption('dev') || $this->getChangeApplication()->inDevelopmentMode();
		if ($this->isDevCommand() && !$devMode)
		{
			throw new \RuntimeException("This is a developer command, you can only run it in developer mode", 21000);
		}
	}
}