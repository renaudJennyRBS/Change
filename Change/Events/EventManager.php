<?php
namespace Change\Events;

/**
 * @name \Change\Events\EventManager
 */
class EventManager extends \Zend\EventManager\EventManager
{		
	/**
	 * @var \Change\Application
	 */
	protected $application;
	
	/**
	 * @param \Change\Application $application
	 */
	public function __construct(\Change\Application $application)
	{
		$this->setApplication($application);
		parent::__construct();		
		$this->registerConfiguredListeners();
	}
	
	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @param \Change\Application $application
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
	}

	protected function registerConfiguredListeners()
	{
		$classes = $this->application->getConfiguration()->getEntry('events/registrationclasses', array());
		foreach ($classes as $className)
		{
			if (class_exists($className))
			{
				new $className($this);
			}
		}
	}
}