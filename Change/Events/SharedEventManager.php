<?php
namespace Change\Events;

use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Change\Events\SharedEventManager
 */
class SharedEventManager extends \Zend\EventManager\SharedEventManager
{
	public function attachConfiguredListeners(\Change\Configuration\Configuration $configuration)
	{
		$classes = $configuration->getEntry('Change/Events/ListenerAggregateClasses', array());
		foreach ($classes as $className)
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