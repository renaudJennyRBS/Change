<?php
namespace Change\Services;

/**
 * @deprecated
 * @name \Change\Services\DefaultServicesTrait
 */
trait DefaultServicesTrait
{
	/**
	 * @deprecated
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @deprecated
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @deprecated
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(\Change\Services\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @deprecated
	 * @return \Change\Services\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @deprecated
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @deprecated
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}
} 