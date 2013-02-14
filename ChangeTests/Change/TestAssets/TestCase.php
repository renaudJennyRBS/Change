<?php

namespace ChangeTests\Change\TestAssets;

/**
 * @name \ChangeTests\Change\TestAssets\TestCase
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
	/**
	 * @return \ChangeTests\Change\TestAssets\Application
	 */
	protected static function getNewApplication()
	{
		return new \ChangeTests\Change\TestAssets\Application();
	}

	/**
	 * @param \Change\Application $application
	 * @return \Change\Application\ApplicationServices
	 */
	protected static function getNewApplicationServices(\Change\Application $application)
	{
		return new \Change\Application\ApplicationServices($application);
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @throws \RuntimeException
	 * @return \Change\Documents\DocumentServices
	 */
	protected static function getNewDocumentServices(\Change\Application\ApplicationServices $applicationServices)
	{
		if (!class_exists('Compilation\Change\Documents\AbstractDocumentServices'))
		{
			throw new \RuntimeException('Documents are not compiled.');
		}
		return new \Change\Documents\DocumentServices($applicationServices);
	}

	/**
	 * @var \ChangeTests\Change\TestAssets\Application
	 */
	protected $application;

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @return \ChangeTests\Change\TestAssets\Application
	 */
	protected function getApplication()
	{
		if (!$this->application)
		{
			$this->application = static::getNewApplication();
		}
		return $this->application;
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	public function getApplicationServices()
	{
		if (!$this->applicationServices)
		{
			$this->applicationServices  = static::getNewApplicationServices($this->getApplication());
		}
		return $this->applicationServices;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices()
	{
		if (!$this->documentServices)
		{
			$this->documentServices = static::getNewDocumentServices($this->getApplicationServices());
		}
		return $this->documentServices;
	}

}
