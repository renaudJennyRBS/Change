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
	 * @param \Change\Configuration\Configuration $configuration
	 */
	public function __construct(\Change\Configuration\Configuration $configuration)
	{
		$this->setConfiguration($configuration);
		parent::__construct();
		$this->registerConfiguredListeners();
	}

	/**
	 * @param \Change\Configuration\Configuration $configuration
	 */
	public function setConfiguration(\Change\Configuration\Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
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