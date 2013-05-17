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
		$classNames = $configuration->getEntry('Change/Events/ListenerAggregateClasses', array());
		$this->registerListenerAggregateClassNames($classNames);
	}

	/**
	 * @param string[] $classNames
	 */
	public function registerListenerAggregateClassNames($classNames)
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
}