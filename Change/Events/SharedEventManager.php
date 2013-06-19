<?php
namespace Change\Events;

use Zend\EventManager\EventManager;
use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateInterface;
/**
 * @name \Change\Events\SharedEventManager
 */
class SharedEventManager extends \Zend\EventManager\SharedEventManager
{
	public function attachConfiguredListeners(\Change\Configuration\Configuration $configuration)
	{
		$classNames = $configuration->getEntry('Change/Events/ListenerAggregateClasses', array());
		$this->registerSharedListenerAggregateClassNames($classNames);
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
				if (class_exists($className))
				{
					$listenerAggregate = new $className();
					if ($listenerAggregate instanceof SharedListenerAggregateInterface)
					{
						$listenerAggregate->attachShared($this);
					}
				}
			}
		}
	}

	/**
	 * @param EventManager $eventManager
	 * @param string[] $classNames
	 */
	public function registerListenerAggregateClassNames(EventManager $eventManager, array $classNames)
	{
		$eventManager->setSharedManager($this);
		if (count($classNames))
		{
			foreach ($classNames as $className)
			{
				if (class_exists($className))
				{
					$listenerAggregate = new $className();
					if ($listenerAggregate instanceof ListenerAggregateInterface)
					{
						$listenerAggregate->attach($eventManager);
					}
				}
			}
		}
	}
}