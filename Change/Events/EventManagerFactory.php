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
		$this->registerSharedListenerAggregateClassNames($application->getConfiguration('Change/Events/ListenerAggregateClasses'));

		if (!\Zend\EventManager\StaticEventManager::hasInstance())
		{
			\Zend\EventManager\StaticEventManager::setInstance($this->sharedEventManager);
		}
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
			}
		}
	}

	/**
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function attachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		$identifiers = array('Documents');

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\ValidateListener())->onValidate($event);
		};
		$events->attach($identifiers, array(\Change\Documents\Events\Event::EVENT_CREATE, \Change\Documents\Events\Event::EVENT_UPDATE), $callBack, 5);

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\DeleteListener())->onDelete($event);
		};
		$events->attach($identifiers, \Change\Documents\Events\Event::EVENT_DELETE, $callBack, 5);

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\DeleteListener())->onDeleted($event);
		};
		$events->attach($identifiers, \Change\Documents\Events\Event::EVENT_DELETED, $callBack, 5);

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\DeleteListener())->onLocalizedDeleted($event);
		};
		$events->attach($identifiers, \Change\Documents\Events\Event::EVENT_LOCALIZED_DELETED, $callBack, 5);

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\DeleteListener())->onCleanUp($event);
		};
		$events->attach('JobManager', 'process_Change_Document_CleanUp', $callBack, 5);

	}
}