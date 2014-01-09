<?php
namespace Change\Events;

/**
* @name \Change\Events\EventManagerFactory
*/
class EventManagerFactory
{
	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Zend\EventManager\SharedEventManager
	 */
	protected $sharedEventManager;

	/**
	 * @var \Zend\Stdlib\Parameters
	 */
	protected $services;

	/**
	 * @param \Change\Application $application
	 */
	function __construct($application)
	{
		$this->application = $application;
		$this->sharedEventManager = new \Zend\EventManager\SharedEventManager();
		$this->services = new \Zend\Stdlib\Parameters();
		$this->attachShared($this->sharedEventManager);
		$classNames = $this->getConfiguredListenerClassNames('Change/Events/ListenerAggregateClasses');
		$this->registerSharedListenerAggregateClassNames($classNames);

		if (!\Zend\EventManager\StaticEventManager::hasInstance())
		{
			\Zend\EventManager\StaticEventManager::setInstance($this->sharedEventManager);
		}
	}

	public function shutdown()
	{
		$this->application = null;
		$this->sharedEventManager = null;
		foreach ($this->services as $service)
		{
			if (is_callable(array($service, 'shutdown')))
			{
				call_user_func(array($service, 'shutdown'));
			}
		}
		$this->services = null;
	}

	/**
	 * @param $configurationEntryName
	 * @return array
	 */
	public function getConfiguredListenerClassNames($configurationEntryName)
	{
		if (is_string($configurationEntryName))
		{
			$configuration = $this->application->getConfiguration();
			$classNames = $configuration->getEntry($configurationEntryName);
			return is_array($classNames) ? $classNames : array();
		}
		return array();
	}

	/**
	 * @return \Zend\EventManager\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		return $this->sharedEventManager;
	}

	/**
	 * @param string $serviceName
	 * @param mixed $service
	 * @return $this
	 */
	public function addSharedService($serviceName, $service)
	{
		$this->services->set($serviceName, $service);
		return $this;
	}

	/**
	 * @param string $serviceName
	 * @return boolean
	 */
	public function hasSharedService($serviceName)
	{
		return isset($this->services[$serviceName]);
	}

	/**
	 * @param string|string[] $identifiers
	 * @return \Change\Events\EventManager
	 */
	public function getNewEventManager($identifiers)
	{
		return new EventManager($identifiers, $this->application, $this->services, $this->sharedEventManager);
	}

	/**
	 * @param string[] $classNames
	 */
	public function registerSharedListenerAggregateClassNames($classNames)
	{
		if (is_array($classNames) && count($classNames))
		{
			foreach ($classNames as $className)
			{
				if (is_string($className) && class_exists($className))
				{
					$listenerAggregate = new $className();
					if ($listenerAggregate instanceof \Zend\EventManager\SharedListenerAggregateInterface)
					{
						$listenerAggregate->attachShared($this->sharedEventManager);
					}
				}
			}
		}
	}

	/**
	 * @param EventManager $eventManager
	 * @param string[] $classNames
	 */
	public function registerListenerAggregateClassNames(EventManager $eventManager, $classNames)
	{
		if (is_array($classNames) && count($classNames))
		{
			foreach ($classNames as $className)
			{
				if (is_string($className) && class_exists($className))
				{
					$listenerAggregate = new $className();
					if ($listenerAggregate instanceof \Zend\EventManager\ListenerAggregateInterface)
					{
						$listenerAggregate->attach($eventManager);
					}
				}
				else
				{
					$applicationServices = $this->services['applicationServices'];
					if ($applicationServices instanceof \Change\Services\ApplicationServices)
					{
						$applicationServices->getLogging()->error($className . ' Listener aggregate Class name not found.');
					}
				}
			}
		}
	}

	/**
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function attachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{

	}
}