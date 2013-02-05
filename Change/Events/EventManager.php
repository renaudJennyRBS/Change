<?php
namespace Change\Events;

/**
 * @name \Change\Events\EventManager
 */
class EventManager extends \Zend\EventManager\EventManager
{		
	/**
	 * @var \Change\Configuration\Configuration
	 */
	protected $configuration;
	
	/**
	 * @param \Change\Application $application
	 */
	public function __construct(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
		parent::__construct();
		$this->registerConfiguredListeners();
	}

	protected function registerConfiguredListeners()
	{
		$classes = $this->configuration->getEntry('events/registrationclasses', array());
		foreach ($classes as $className)
		{
			if (class_exists($className))
			{
				new $className($this);
			}
		}
	}
}